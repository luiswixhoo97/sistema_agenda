<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VentaCitaController extends Controller
{
    /**
     * Crear venta desde una cita existente
     * 
     * POST /api/admin/ventas-citas/crear-desde-cita/{cita_id}
     */
    public function crearDesdeCita(Request $request, int $citaId): JsonResponse
    {
        $cita = Cita::with(['servicios.servicio', 'cliente', 'empleado'])->find($citaId);

        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada',
            ], 404);
        }

        // Verificar si ya existe una venta para esta cita
        $ventaExistente = VentaDetalle::where('cita_id', $citaId)
            ->whereHas('venta', fn($q) => $q->where('estado', '!=', Venta::ESTADO_CANCELADA))
            ->first();

        if ($ventaExistente) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una venta para esta cita',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calcular total de servicios de la cita
            $subtotal = 0;
            $detalles = [];

            if ($cita->servicios->count() > 0) {
                foreach ($cita->servicios as $citaServicio) {
                    $precio = $citaServicio->precio_aplicado;
                    $subtotal += $precio;
                    
                    $detalles[] = [
                        'tipo' => VentaDetalle::TIPO_SERVICIO,
                        'servicio_id' => $citaServicio->servicio_id,
                        'cita_id' => $cita->id,
                        'cantidad' => 1,
                        'precio_unitario' => $precio,
                        'descuento' => 0,
                        'impuesto' => 0,
                        'subtotal_linea' => $precio,
                    ];
                }
            } else {
                // Si no tiene servicios múltiples, usar el servicio principal
                $precio = $cita->precio_final ?? $cita->servicio->precio ?? 0;
                $subtotal = $precio;
                
                $detalles[] = [
                    'tipo' => VentaDetalle::TIPO_SERVICIO,
                    'servicio_id' => $cita->servicio_id,
                    'cita_id' => $cita->id,
                    'cantidad' => 1,
                    'precio_unitario' => $precio,
                    'descuento' => 0,
                    'impuesto' => 0,
                    'subtotal_linea' => $precio,
                ];
            }

            // Crear venta
            $venta = Venta::create([
                'cliente_id' => $cita->cliente_id,
                'fecha_venta' => $cita->fecha_hora,
                'subtotal' => $subtotal,
                'descuento_general' => 0,
                'impuesto_total' => 0,
                'total' => $subtotal,
                'total_pagado' => 0,
                'saldo_pendiente' => $subtotal,
                'estado' => Venta::ESTADO_PENDIENTE_PAGO,
                'notas' => "Venta generada desde cita #{$cita->id}",
            ]);

            // Crear detalles
            foreach ($detalles as $detalle) {
                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'tipo' => $detalle['tipo'],
                    'servicio_id' => $detalle['servicio_id'],
                    'cita_id' => $detalle['cita_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'descuento' => $detalle['descuento'],
                    'impuesto' => $detalle['impuesto'],
                    'subtotal_linea' => $detalle['subtotal_linea'],
                ]);
            }

            Auditoria::registrar('crear', 'ventas', $venta->id, null, $venta->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta creada desde cita correctamente',
                'data' => [
                    'venta_id' => $venta->id,
                    'cita_id' => $cita->id,
                    'total' => $venta->total,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear venta desde cita: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Agregar productos a venta de cita
     * 
     * POST /api/admin/ventas-citas/{venta_id}/productos
     */
    public function agregarProductos(Request $request, int $ventaId): JsonResponse
    {
        $venta = Venta::find($ventaId);

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
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $nuevoSubtotal = $venta->subtotal;

            foreach ($request->productos as $productoData) {
                $producto = Producto::findOrFail($productoData['producto_id']);

                // Validar stock
                if (!$producto->tieneStock($productoData['cantidad'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para producto: {$producto->nombre}. Disponible: {$producto->inventario_actual}",
                    ], 422);
                }

                $precioUnitario = $productoData['precio_unitario'] ?? $producto->precio;
                $subtotalLinea = $precioUnitario * $productoData['cantidad'];
                $nuevoSubtotal += $subtotalLinea;

                // Crear detalle
                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'tipo' => VentaDetalle::TIPO_PRODUCTO,
                    'producto_id' => $producto->id,
                    'cantidad' => $productoData['cantidad'],
                    'precio_unitario' => $precioUnitario,
                    'descuento' => 0,
                    'impuesto' => 0,
                    'subtotal_linea' => $subtotalLinea,
                ]);

                // Actualizar inventario
                $producto->inventario_actual -= $productoData['cantidad'];
                $producto->save();

                // Crear movimiento de inventario
                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'tipo' => MovimientoInventario::TIPO_VENTA,
                    'cantidad' => $productoData['cantidad'],
                    'referencia_id' => $venta->id,
                    'referencia_tipo' => 'venta',
                    'user_id' => auth()->id(),
                    'created_at' => now(),
                ]);
            }

            // Actualizar totales de la venta
            $nuevoTotal = $nuevoSubtotal - $venta->descuento_general + $venta->impuesto_total;
            $venta->subtotal = $nuevoSubtotal;
            $venta->total = $nuevoTotal;
            $venta->saldo_pendiente = $nuevoTotal - $venta->total_pagado;
            $venta->save();

            Auditoria::registrar('actualizar', 'ventas', $venta->id, null, $venta->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Productos agregados correctamente',
                'data' => [
                    'venta_id' => $venta->id,
                    'nuevo_total' => $venta->total,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar productos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalizar venta de cita
     * 
     * POST /api/admin/ventas-citas/{venta_id}/finalizar
     */
    public function finalizar(Request $request, int $ventaId): JsonResponse
    {
        $venta = Venta::with('detalles')->find($ventaId);

        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada',
            ], 404);
        }

        // Verificar que tenga al menos un detalle
        if ($venta->detalles->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'La venta no tiene detalles',
            ], 422);
        }

        // La venta se considera finalizada cuando se registran los pagos
        // Este método solo marca la venta como lista para pago
        return response()->json([
            'success' => true,
            'message' => 'Venta lista para procesar pagos',
            'data' => [
                'venta_id' => $venta->id,
                'total' => $venta->total,
                'saldo_pendiente' => $venta->saldo_pendiente,
            ],
        ]);
    }
}
