<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    /**
     * Registrar entrada manual de inventario
     * 
     * POST /api/admin/inventario/entrada
     */
    public function entradaManual(Request $request): JsonResponse
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'nullable|string|max:255',
            'notas' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $producto = Producto::findOrFail($request->producto_id);

            // Crear movimiento
            $movimiento = MovimientoInventario::create([
                'producto_id' => $producto->id,
                'tipo' => MovimientoInventario::TIPO_ENTRADA_MANUAL,
                'cantidad' => $request->cantidad,
                'motivo' => $request->motivo,
                'user_id' => auth()->id(),
                'notas' => $request->notas,
                'created_at' => now(),
            ]);

            // Actualizar inventario del producto
            $producto->inventario_actual += $request->cantidad;
            $producto->save();

            Auditoria::registrar('entrada_inventario', 'movimientos_inventario', $movimiento->id, null, $movimiento->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrada de inventario registrada correctamente',
                'data' => [
                    'movimiento' => [
                        'id' => $movimiento->id,
                        'tipo' => $movimiento->tipo,
                        'cantidad' => $movimiento->cantidad,
                        'motivo' => $movimiento->motivo,
                        'created_at' => $movimiento->created_at,
                    ],
                    'producto' => [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'inventario_actual' => $producto->inventario_actual,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar entrada: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registrar salida manual de inventario
     * 
     * POST /api/admin/inventario/salida
     */
    public function salidaManual(Request $request): JsonResponse
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|string|max:255',
            'notas' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $producto = Producto::findOrFail($request->producto_id);

            // Validar stock disponible
            if ($producto->inventario_actual < $request->cantidad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente. Disponible: ' . $producto->inventario_actual,
                ], 422);
            }

            // Crear movimiento
            $movimiento = MovimientoInventario::create([
                'producto_id' => $producto->id,
                'tipo' => MovimientoInventario::TIPO_SALIDA_MANUAL,
                'cantidad' => $request->cantidad,
                'motivo' => $request->motivo,
                'user_id' => auth()->id(),
                'notas' => $request->notas,
                'created_at' => now(),
            ]);

            // Actualizar inventario del producto
            $producto->inventario_actual -= $request->cantidad;
            $producto->save();

            Auditoria::registrar('salida_inventario', 'movimientos_inventario', $movimiento->id, null, $movimiento->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salida de inventario registrada correctamente',
                'data' => [
                    'movimiento' => [
                        'id' => $movimiento->id,
                        'tipo' => $movimiento->tipo,
                        'cantidad' => $movimiento->cantidad,
                        'motivo' => $movimiento->motivo,
                        'created_at' => $movimiento->created_at,
                    ],
                    'producto' => [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'inventario_actual' => $producto->inventario_actual,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar salida: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ajuste de inventario (entrada o salida)
     * 
     * POST /api/admin/inventario/ajuste
     */
    public function ajuste(Request $request): JsonResponse
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'nuevo_inventario' => 'required|integer|min:0',
            'motivo' => 'required|string|max:255',
            'notas' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $producto = Producto::findOrFail($request->producto_id);
            $inventarioAnterior = $producto->inventario_actual;
            $diferencia = $request->nuevo_inventario - $inventarioAnterior;

            if ($diferencia == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El inventario no ha cambiado',
                ], 422);
            }

            $tipo = $diferencia > 0 
                ? MovimientoInventario::TIPO_ENTRADA_MANUAL 
                : MovimientoInventario::TIPO_SALIDA_MANUAL;

            // Crear movimiento
            $movimiento = MovimientoInventario::create([
                'producto_id' => $producto->id,
                'tipo' => $tipo,
                'cantidad' => abs($diferencia),
                'motivo' => $request->motivo . ' (Ajuste: ' . $inventarioAnterior . ' → ' . $request->nuevo_inventario . ')',
                'user_id' => auth()->id(),
                'notas' => $request->notas,
                'created_at' => now(),
            ]);

            // Actualizar inventario del producto
            $producto->inventario_actual = $request->nuevo_inventario;
            $producto->save();

            Auditoria::registrar('ajuste_inventario', 'movimientos_inventario', $movimiento->id, null, $movimiento->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Ajuste de inventario registrado correctamente',
                'data' => [
                    'movimiento' => [
                        'id' => $movimiento->id,
                        'tipo' => $movimiento->tipo,
                        'cantidad' => $movimiento->cantidad,
                        'motivo' => $movimiento->motivo,
                        'created_at' => $movimiento->created_at,
                    ],
                    'producto' => [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'inventario_anterior' => $inventarioAnterior,
                        'inventario_actual' => $producto->inventario_actual,
                        'diferencia' => $diferencia,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar ajuste: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar kardex de inventario por producto
     * 
     * GET /api/admin/inventario/kardex/{producto_id}
     */
    public function kardex(Request $request, int $productoId): JsonResponse
    {
        $producto = Producto::find($productoId);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        $fechaInicio = $request->get('fecha_inicio', now()->subMonths(3)->startOfDay());
        $fechaFin = $request->get('fecha_fin', now()->endOfDay());

        $movimientos = MovimientoInventario::where('producto_id', $productoId)
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->orderBy('created_at', 'desc')
            ->get();

        $saldoInicial = $producto->inventario_actual;
        $entradas = 0;
        $salidas = 0;

        foreach ($movimientos as $mov) {
            if ($mov->tipo === MovimientoInventario::TIPO_ENTRADA_MANUAL || 
                $mov->tipo === MovimientoInventario::TIPO_VENTA) {
                $entradas += $mov->cantidad;
            } else {
                $salidas += $mov->cantidad;
            }
        }

        // Calcular saldo inicial (inventario actual - entradas + salidas)
        $saldoInicialCalculado = $producto->inventario_actual - $entradas + $salidas;

        return response()->json([
            'success' => true,
            'data' => [
                'producto' => [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'inventario_actual' => $producto->inventario_actual,
                ],
                'periodo' => [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                ],
                'resumen' => [
                    'saldo_inicial' => max(0, $saldoInicialCalculado),
                    'entradas' => $entradas,
                    'salidas' => $salidas,
                    'saldo_final' => $producto->inventario_actual,
                ],
                'movimientos' => $movimientos->map(fn($m) => [
                    'id' => $m->id,
                    'tipo' => $m->tipo,
                    'cantidad' => $m->cantidad,
                    'motivo' => $m->motivo,
                    'referencia_tipo' => $m->referencia_tipo,
                    'referencia_id' => $m->referencia_id,
                    'usuario' => $m->usuario ? $m->usuario->nombre : null,
                    'notas' => $m->notas,
                    'created_at' => $m->created_at,
                ]),
            ],
        ]);
    }

    /**
     * Listar movimientos de inventario
     * 
     * GET /api/admin/inventario/movimientos
     */
    public function movimientos(Request $request): JsonResponse
    {
        $query = MovimientoInventario::with(['producto', 'usuario']);

        // Filtros
        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->has('fecha_inicio')) {
            $query->where('created_at', '>=', $request->fecha_inicio);
        }
        if ($request->has('fecha_fin')) {
            $query->where('created_at', '<=', $request->fecha_fin);
        }

        $movimientos = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $movimientos->map(fn($m) => [
                'id' => $m->id,
                'producto' => [
                    'id' => $m->producto->id,
                    'codigo' => $m->producto->codigo,
                    'nombre' => $m->producto->nombre,
                ],
                'tipo' => $m->tipo,
                'cantidad' => $m->cantidad,
                'motivo' => $m->motivo,
                'referencia_tipo' => $m->referencia_tipo,
                'referencia_id' => $m->referencia_id,
                'usuario' => $m->usuario ? [
                    'id' => $m->usuario->id,
                    'nombre' => $m->usuario->nombre,
                ] : null,
                'notas' => $m->notas,
                'created_at' => $m->created_at,
            ]),
            'pagination' => [
                'current_page' => $movimientos->currentPage(),
                'last_page' => $movimientos->lastPage(),
                'total' => $movimientos->total(),
            ],
        ]);
    }
}
