<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    /**
     * Listar ventas
     * 
     * GET /api/admin/ventas
     */
    public function index(Request $request): JsonResponse
    {
        $query = Venta::with(['cliente', 'detalles.producto', 'detalles.servicio']);

        // Filtros
        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->has('fecha_inicio')) {
            $query->where('fecha_venta', '>=', $request->fecha_inicio);
        }
        if ($request->has('fecha_fin')) {
            $query->where('fecha_venta', '<=', $request->fecha_fin);
        }

        $ventas = $query->orderBy('fecha_venta', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $ventas->map(fn($v) => $this->formatearVenta($v)),
            'pagination' => [
                'current_page' => $ventas->currentPage(),
                'last_page' => $ventas->lastPage(),
                'total' => $ventas->total(),
            ],
        ]);
    }

    /**
     * Ver venta
     * 
     * GET /api/admin/ventas/{id}
     */
    public function show(int $id): JsonResponse
    {
        $venta = Venta::with([
            'cliente',
            'detalles.producto',
            'detalles.servicio',
            'detalles.cita',
            'detalles.promocion',
            'pagos.metodoPago',
        ])->find($id);

        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatearVentaCompleta($venta),
        ]);
    }

    /**
     * Crear venta directa (punto de venta)
     * 
     * POST /api/admin/ventas
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'nullable|exists:clientes,id',
            'detalles' => 'required|array|min:1',
            'detalles.*.tipo' => 'required|in:producto,servicio',
            'detalles.*.producto_id' => 'required_if:detalles.*.tipo,producto|exists:productos,id',
            'detalles.*.servicio_id' => 'required_if:detalles.*.tipo,servicio|exists:servicios,id',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.descuento' => 'nullable|numeric|min:0',
            'detalles.*.impuesto' => 'nullable|numeric|min:0',
            'descuento_general' => 'nullable|numeric|min:0',
            'impuesto_total' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Validar stock de productos
            foreach ($request->detalles as $detalle) {
                if ($detalle['tipo'] === 'producto') {
                    $producto = Producto::findOrFail($detalle['producto_id']);
                    if (!$producto->tieneStock($detalle['cantidad'])) {
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para producto: {$producto->nombre}. Disponible: {$producto->inventario_actual}",
                        ], 422);
                    }
                }
            }

            // Calcular totales
            $subtotal = 0;
            foreach ($request->detalles as $detalle) {
                $subtotalLinea = ($detalle['precio_unitario'] * $detalle['cantidad']) 
                    - ($detalle['descuento'] ?? 0) 
                    + ($detalle['impuesto'] ?? 0);
                $subtotal += $subtotalLinea;
            }

            $descuentoGeneral = $request->descuento_general ?? 0;
            $impuestoTotal = $request->impuesto_total ?? 0;
            $total = $subtotal - $descuentoGeneral + $impuestoTotal;

            // Crear venta
            $venta = Venta::create([
                'cliente_id' => $request->cliente_id,
                'fecha_venta' => now(),
                'subtotal' => $subtotal,
                'descuento_general' => $descuentoGeneral,
                'impuesto_total' => $impuestoTotal,
                'total' => $total,
                'total_pagado' => 0,
                'saldo_pendiente' => $total,
                'estado' => Venta::ESTADO_PENDIENTE_PAGO,
                'notas' => $request->notas,
            ]);

            // Crear detalles y actualizar inventario
            foreach ($request->detalles as $detalle) {
                $subtotalLinea = ($detalle['precio_unitario'] * $detalle['cantidad']) 
                    - ($detalle['descuento'] ?? 0) 
                    + ($detalle['impuesto'] ?? 0);

                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'tipo' => $detalle['tipo'],
                    'producto_id' => $detalle['tipo'] === 'producto' ? $detalle['producto_id'] : null,
                    'servicio_id' => $detalle['tipo'] === 'servicio' ? $detalle['servicio_id'] : null,
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'descuento' => $detalle['descuento'] ?? 0,
                    'impuesto' => $detalle['impuesto'] ?? 0,
                    'subtotal_linea' => $subtotalLinea,
                ]);

                // Actualizar inventario si es producto
                if ($detalle['tipo'] === 'producto') {
                    $producto = Producto::findOrFail($detalle['producto_id']);
                    $producto->inventario_actual -= $detalle['cantidad'];
                    $producto->save();

                    // Crear movimiento de inventario
                    MovimientoInventario::create([
                        'producto_id' => $producto->id,
                        'tipo' => MovimientoInventario::TIPO_VENTA,
                        'cantidad' => $detalle['cantidad'],
                        'referencia_id' => $venta->id,
                        'referencia_tipo' => 'venta',
                        'user_id' => auth()->id(),
                        'created_at' => now(),
                    ]);
                }
            }

            Auditoria::registrar('crear', 'ventas', $venta->id, null, $venta->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta creada correctamente',
                'data' => $this->formatearVentaCompleta($venta->load(['detalles.producto', 'detalles.servicio'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear venta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar venta (permite agregar productos/servicios si está pendiente o parcial)
     * 
     * PUT /api/admin/ventas/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $venta = Venta::with('detalles')->find($id);

        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada',
            ], 404);
        }

        if (!$venta->puedeModificarse()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar una venta completada o cancelada',
            ], 422);
        }

        $request->validate([
            'notas' => 'nullable|string',
            'detalles' => 'nullable|array',
            'detalles.*.tipo' => 'required_with:detalles|in:producto,servicio',
            'detalles.*.producto_id' => 'required_if:detalles.*.tipo,producto|exists:productos,id',
            'detalles.*.servicio_id' => 'required_if:detalles.*.tipo,servicio|exists:servicios,id',
            'detalles.*.cantidad' => 'required_with:detalles|integer|min:1',
            'detalles.*.precio_unitario' => 'required_with:detalles|numeric|min:0',
            'detalles.*.descuento' => 'nullable|numeric|min:0',
            'detalles.*.impuesto' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $datosAnteriores = $venta->toArray();
            $nuevoSubtotal = $venta->subtotal;

            // Si se envían nuevos detalles, agregarlos
            if ($request->has('detalles') && count($request->detalles) > 0) {
                // Validar stock de productos
                foreach ($request->detalles as $detalle) {
                    if ($detalle['tipo'] === 'producto') {
                        $producto = Producto::findOrFail($detalle['producto_id']);
                        if (!$producto->tieneStock($detalle['cantidad'])) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "Stock insuficiente para producto: {$producto->nombre}. Disponible: {$producto->inventario_actual}",
                            ], 422);
                        }
                    }
                }

                // Agregar nuevos detalles
                foreach ($request->detalles as $detalle) {
                    $subtotalLinea = ($detalle['precio_unitario'] * $detalle['cantidad']) 
                        - ($detalle['descuento'] ?? 0) 
                        + ($detalle['impuesto'] ?? 0);
                    $nuevoSubtotal += $subtotalLinea;

                    VentaDetalle::create([
                        'venta_id' => $venta->id,
                        'tipo' => $detalle['tipo'],
                        'producto_id' => $detalle['tipo'] === 'producto' ? $detalle['producto_id'] : null,
                        'servicio_id' => $detalle['tipo'] === 'servicio' ? $detalle['servicio_id'] : null,
                        'cantidad' => $detalle['cantidad'],
                        'precio_unitario' => $detalle['precio_unitario'],
                        'descuento' => $detalle['descuento'] ?? 0,
                        'impuesto' => $detalle['impuesto'] ?? 0,
                        'subtotal_linea' => $subtotalLinea,
                    ]);

                    // Actualizar inventario si es producto
                    if ($detalle['tipo'] === 'producto') {
                        $producto = Producto::findOrFail($detalle['producto_id']);
                        $producto->inventario_actual -= $detalle['cantidad'];
                        $producto->save();

                        // Crear movimiento de inventario
                        MovimientoInventario::create([
                            'producto_id' => $producto->id,
                            'tipo' => MovimientoInventario::TIPO_VENTA,
                            'cantidad' => $detalle['cantidad'],
                            'referencia_id' => $venta->id,
                            'referencia_tipo' => 'venta',
                            'user_id' => auth()->id(),
                            'created_at' => now(),
                        ]);
                    }
                }

                // Actualizar totales de la venta (manteniendo el total_pagado)
                $nuevoTotal = $nuevoSubtotal - $venta->descuento_general + $venta->impuesto_total;
                $venta->subtotal = $nuevoSubtotal;
                $venta->total = $nuevoTotal;
                $venta->saldo_pendiente = $nuevoTotal - $venta->total_pagado;
            }

            // Actualizar notas si se proporciona
            if ($request->has('notas')) {
                $venta->notas = $request->notas;
            }

            $venta->save();

            Auditoria::registrar('actualizar', 'ventas', $venta->id, $datosAnteriores, $venta->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta actualizada correctamente',
                'data' => $this->formatearVentaCompleta($venta->load(['detalles.producto', 'detalles.servicio'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar venta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancelar venta
     * 
     * DELETE /api/admin/ventas/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $venta = Venta::with('detalles.producto')->find($id);

        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada',
            ], 404);
        }

        if ($venta->estaCancelada()) {
            return response()->json([
                'success' => false,
                'message' => 'La venta ya está cancelada',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Revertir inventario de productos
            foreach ($venta->detalles as $detalle) {
                if ($detalle->tipo === 'producto' && $detalle->producto) {
                    $producto = $detalle->producto;
                    $producto->inventario_actual += $detalle->cantidad;
                    $producto->save();

                    // Crear movimiento de reversión
                    MovimientoInventario::create([
                        'producto_id' => $producto->id,
                        'tipo' => MovimientoInventario::TIPO_ENTRADA_MANUAL,
                        'cantidad' => $detalle->cantidad,
                        'motivo' => 'Reversión por cancelación de venta #' . $venta->id,
                        'referencia_id' => $venta->id,
                        'referencia_tipo' => 'venta_cancelada',
                        'user_id' => auth()->id(),
                        'created_at' => now(),
                    ]);
                }
            }

            $venta->estado = Venta::ESTADO_CANCELADA;
            $venta->save();

            Auditoria::registrar('cancelar', 'ventas', $venta->id, null, $venta->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta cancelada correctamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar venta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar productos para agregar a venta
     * 
     * GET /api/admin/ventas/productos/buscar
     */
    public function buscarProductos(Request $request): JsonResponse
    {
        $request->validate([
            'termino' => 'required|string|min:1',
        ]);

        $productos = Producto::with('categoria')
            ->where('active', true)
            ->buscar($request->termino)
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos->map(fn($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'precio' => $p->precio,
                'precio_texto' => '$' . number_format($p->precio, 2),
                'inventario_actual' => $p->inventario_actual,
                'tiene_stock' => $p->inventario_actual > 0,
                'categoria' => $p->categoria ? [
                    'id' => $p->categoria->id,
                    'nombre' => $p->categoria->nombre,
                ] : null,
            ]),
        ]);
    }

    /**
     * Calcular totales de venta
     * 
     * POST /api/admin/ventas/calcular-totales
     */
    public function calcularTotales(Request $request): JsonResponse
    {
        $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.cantidad' => 'required|integer|min:1',
            'detalles.*.descuento' => 'nullable|numeric|min:0',
            'detalles.*.impuesto' => 'nullable|numeric|min:0',
            'descuento_general' => 'nullable|numeric|min:0',
        ]);

        $subtotal = 0;
        foreach ($request->detalles as $detalle) {
            $subtotalLinea = ($detalle['precio_unitario'] * $detalle['cantidad']) 
                - ($detalle['descuento'] ?? 0) 
                + ($detalle['impuesto'] ?? 0);
            $subtotal += $subtotalLinea;
        }

        $descuentoGeneral = $request->descuento_general ?? 0;
        $impuestoTotal = $request->impuesto_total ?? 0;
        $total = $subtotal - $descuentoGeneral + $impuestoTotal;

        return response()->json([
            'success' => true,
            'data' => [
                'subtotal' => $subtotal,
                'descuento_general' => $descuentoGeneral,
                'impuesto_total' => $impuestoTotal,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Formatear venta básica
     */
    private function formatearVenta(Venta $venta): array
    {
        return [
            'id' => $venta->id,
            'cliente' => $venta->cliente ? [
                'id' => $venta->cliente->id,
                'nombre' => $venta->cliente->nombre,
            ] : null,
            'fecha_venta' => $venta->fecha_venta,
            'subtotal' => $venta->subtotal,
            'descuento_general' => $venta->descuento_general,
            'impuesto_total' => $venta->impuesto_total,
            'total' => $venta->total,
            'total_pagado' => $venta->total_pagado,
            'saldo_pendiente' => $venta->saldo_pendiente,
            'estado' => $venta->estado,
            'requiere_anticipo' => $venta->requiere_anticipo,
            'monto_anticipo_requerido' => $venta->monto_anticipo_requerido,
            'monto_anticipo_pagado' => $venta->monto_anticipo_pagado,
            'notas' => $venta->notas,
            'created_at' => $venta->created_at,
        ];
    }

    /**
     * Formatear venta completa con detalles
     */
    private function formatearVentaCompleta(Venta $venta): array
    {
        $data = $this->formatearVenta($venta);
        
        $data['detalles'] = $venta->detalles->map(fn($d) => [
            'id' => $d->id,
            'tipo' => $d->tipo,
            'producto' => $d->producto ? [
                'id' => $d->producto->id,
                'codigo' => $d->producto->codigo,
                'nombre' => $d->producto->nombre,
            ] : null,
            'servicio' => $d->servicio ? [
                'id' => $d->servicio->id,
                'nombre' => $d->servicio->nombre,
            ] : null,
            'cantidad' => $d->cantidad,
            'precio_unitario' => $d->precio_unitario,
            'descuento' => $d->descuento,
            'impuesto' => $d->impuesto,
            'subtotal_linea' => $d->subtotal_linea,
        ]);

        $data['pagos'] = $venta->pagos->map(fn($p) => [
            'id' => $p->id,
            'metodo_pago' => [
                'id' => $p->metodoPago->id,
                'nombre' => $p->metodoPago->nombre,
            ],
            'monto' => $p->monto,
            'estado_pago' => $p->estado_pago,
            'created_at' => $p->created_at,
        ]);

        return $data;
    }
}
