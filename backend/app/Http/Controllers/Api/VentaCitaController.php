<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaPago;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\MovimientoInventario;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VentaCitaController extends Controller
{
    /**
     * Crear venta desde una cita existente
     * 
     * POST /api/admin/ventas-citas/crear-desde-cita/{cita_id}
     */
    public function crearDesdeCita(Request $request, int $citaId): JsonResponse
    {
        $cita = Cita::with(['servicio', 'cliente', 'empleado'])->find($citaId);

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

            // Usar el servicio único de la cita
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
     * Crear venta parcial con anticipo desde cita(s)
     * 
     * POST /api/admin/ventas-citas/crear-parcial
     * POST /api/empleado/ventas-citas/crear-parcial
     */
    public function crearVentaParcialConAnticipo(Request $request): JsonResponse
    {
        $request->validate([
            'cita_id' => 'nullable|integer|exists:citas,id',
            'token_qr' => 'nullable|string',
            'monto_anticipo' => 'required|numeric|min:0',
            'metodo_pago_id' => 'required|integer|exists:metodos_pago,id',
            'notas' => 'nullable|string|max:500',
        ]);

        // Validar que se proporcione cita_id o token_qr
        if (!$request->has('cita_id') && !$request->has('token_qr')) {
            return response()->json([
                'success' => false,
                'message' => 'Debe proporcionar cita_id o token_qr',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Buscar citas coordinadas
            $citas = collect();
            if ($request->has('cita_id')) {
                $cita = Cita::find($request->cita_id);
                if (!$cita) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cita no encontrada',
                    ], 404);
                }
                
                // Si es empleado, verificar que sea su cita
                $usuario = auth()->user();
                if ($usuario->isEmpleado() && !$usuario->isAdmin()) {
                    $empleado = $usuario->empleado;
                    if ($cita->empleado_id !== $empleado->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No tienes permisos para crear venta parcial de esta cita',
                        ], 403);
                    }
                }

                // Buscar todas las citas coordinadas (mismo token_qr)
                $citas = Cita::where('token_qr', $cita->token_qr)
                    ->whereNull('deleted_at')
                    ->get();
            } else {
                // Buscar por token_qr
                $citas = Cita::where('token_qr', $request->token_qr)
                    ->whereNull('deleted_at')
                    ->get();
            }

            if ($citas->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron citas',
                ], 404);
            }

            // Verificar que las citas requieran anticipo
            $citaPrincipal = $citas->first();
            if (!$citaPrincipal->requiere_anticipo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta cita no requiere anticipo',
                ], 422);
            }

            // Verificar que no exista ya una venta parcial
            $citasConVenta = $citas->filter(fn($c) => $c->venta_id !== null);
            if ($citasConVenta->isNotEmpty()) {
                $ventaExistente = Venta::find($citasConVenta->first()->venta_id);
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una venta parcial para estas citas',
                    'venta_id' => $ventaExistente->id,
                ], 422);
            }

            // Calcular total de servicios de todas las citas
            $subtotal = $citas->sum('precio_final');
            $total = $subtotal;

            // Validar que el monto del anticipo no exceda el total
            if ($request->monto_anticipo > $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'El monto del anticipo no puede exceder el total de la venta',
                ], 422);
            }

            // Crear venta parcial
            $venta = Venta::create([
                'cliente_id' => $citaPrincipal->cliente_id,
                'fecha_venta' => $citaPrincipal->fecha_hora,
                'subtotal' => $subtotal,
                'descuento_general' => 0,
                'impuesto_total' => 0,
                'total' => $total,
                'total_pagado' => 0, // Se actualizará al crear el pago
                'saldo_pendiente' => $total, // Se actualizará al crear el pago
                'estado' => Venta::ESTADO_PARCIAL,
                'requiere_anticipo' => true,
                'monto_anticipo_requerido' => $citaPrincipal->monto_anticipo_requerido,
                'monto_anticipo_pagado' => $request->monto_anticipo, // Para trazabilidad
                'notas' => $request->notas ?? "Venta parcial creada desde citas con anticipo. Citas: " . $citas->pluck('id')->implode(', '),
            ]);

            // Crear detalles de venta para cada cita
            foreach ($citas as $cita) {
                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'tipo' => VentaDetalle::TIPO_SERVICIO,
                    'servicio_id' => $cita->servicio_id,
                    'cita_id' => $cita->id,
                    'promocion_id' => $cita->promocion_id,
                    'cantidad' => 1,
                    'precio_unitario' => $cita->precio_final,
                    'descuento' => 0,
                    'impuesto' => 0,
                    'subtotal_linea' => $cita->precio_final,
                ]);

                // Relacionar cita con venta
                $cita->venta_id = $venta->id;
                $cita->save();
            }

            // Crear pago del anticipo
            $metodoPago = MetodoPago::findOrFail($request->metodo_pago_id);
            $ventaPago = VentaPago::create([
                'venta_id' => $venta->id,
                'metodo_pago_id' => $request->metodo_pago_id,
                'monto' => $request->monto_anticipo,
                'estado_pago' => VentaPago::ESTADO_APROBADO, // Ya fue recibido
                'notas' => 'Anticipo recibido por transferencia',
                'user_id' => auth()->id(),
            ]);

            // Actualizar saldo de la venta
            $venta->actualizarSaldo();

            Auditoria::registrar('crear', 'ventas', $venta->id, null, $venta->toArray());

            DB::commit();

            Log::info("Venta parcial creada con anticipo", [
                'venta_id' => $venta->id,
                'citas' => $citas->pluck('id')->toArray(),
                'monto_anticipo' => $request->monto_anticipo,
                'total' => $total,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Venta parcial creada correctamente',
                'data' => [
                    'venta_id' => $venta->id,
                    'total' => $venta->total,
                    'monto_anticipo_pagado' => $request->monto_anticipo,
                    'saldo_pendiente' => $venta->saldo_pendiente,
                    'citas_relacionadas' => $citas->pluck('id')->toArray(),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear venta parcial: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear venta parcial: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validar QR sin modificar datos (muestra información para validación)
     * 
     * POST /api/admin/ventas-citas/validate-qr/{token}
     * POST /api/empleado/ventas-citas/validate-qr/{token}
     */
    public function validarQr(string $token): JsonResponse
    {
        $user = auth()->user();
        $user->load('role', 'empleado');
        $esAdmin = $user->isAdmin();
        $esEmpleado = $user->isEmpleado() && $user->empleado;

        // Validar permisos básicos
        if (!$esEmpleado && !$esAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para validar QR.',
                'code' => 403
            ]);
        }

        // Buscar citas por token
        $citas = Cita::where('token_qr', $token)
            ->whereNull('deleted_at')
            ->get();

        if ($citas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Código QR no válido o cita no encontrada.',
                'code' => 404
            ]);
        }

        // Validar permisos de empleado (solo puede ver sus citas)
        if ($esEmpleado && !$esAdmin) {
            $empleadoId = $user->empleado->id;
            $citasNoAsignadas = $citas->filter(function ($cita) use ($empleadoId) {
                return $cita->empleado_id !== $empleadoId;
            });
            
            if ($citasNoAsignadas->isNotEmpty()) {
                Log::warning("Empleado intentando validar QR con citas no asignadas", [
                    'empleado_id' => $empleadoId,
                    'token_qr' => $token,
                    'citas_no_asignadas' => $citasNoAsignadas->pluck('id')->toArray(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Algunas citas en este QR no te están asignadas.',
                    'code' => 403
                ]);
            }
        }

        // Cargar relaciones completas para mostrar detalles
        $citas->load(['cliente', 'servicio', 'empleado']);

        // Verificar si existe venta parcial para estas citas
        $ventaExistente = null;
        $citasConVenta = $citas->filter(fn($c) => $c->venta_id !== null);
        
        if ($citasConVenta->isNotEmpty()) {
            $ventaExistente = Venta::find($citasConVenta->first()->venta_id);
            if ($ventaExistente) {
                $ventaExistente->load(['cliente', 'detalles.servicio', 'pagos.metodoPago']);
            }
        }

        // Calcular totales
        $totalServicios = $citas->sum('precio_final');

        return response()->json([
            'success' => true,
            'data' => [
                'citas' => $citas,
                'venta_existente' => $ventaExistente ? true : false,
                'venta' => $ventaExistente,
                'total_servicios' => $totalServicios,
                'cantidad_citas' => $citas->count(),
            ]
        ]);
    }

    /**
     * Procesar escaneo de QR (completar citas y generar venta)
     * 
     * POST /api/admin/ventas-citas/complete-qr/{token}
     * POST /api/empleado/ventas-citas/complete-qr/{token}
     */
    public function completarQr(string $token): JsonResponse
    {
        $usuario = auth()->user();
        
        // Usar el servicio de QR existente para procesar
        $qrService = new \App\Services\QrService();
        $resultado = $qrService->procesarEscaneo($token, $usuario);
        
        return response()->json($resultado);
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
