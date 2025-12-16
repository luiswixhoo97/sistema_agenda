<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Models\MetodoPago;
use App\Models\WebhookPago;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoController extends Controller
{
    /**
     * Registrar pago de venta (efectivo, tarjeta, transferencia)
     * 
     * POST /api/admin/pagos/registrar
     */
    public function registrarPago(Request $request): JsonResponse
    {
        $request->validate([
            'venta_id' => 'required|exists:ventas,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'monto' => 'required|numeric|min:0.01',
            'monto_recibido' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $venta = Venta::findOrFail($request->venta_id);
            $metodoPago = MetodoPago::findOrFail($request->metodo_pago_id);

            // Validar que el monto no exceda el saldo pendiente
            $saldoPendiente = $venta->saldo_pendiente;
            if ($request->monto > $saldoPendiente) {
                return response()->json([
                    'success' => false,
                    'message' => "El monto excede el saldo pendiente. Saldo pendiente: $" . number_format($saldoPendiente, 2),
                ], 422);
            }

            // Calcular cambio si es efectivo
            $cambio = 0;
            if ($metodoPago->es_efectivo && $request->monto_recibido) {
                $cambio = $request->monto_recibido - $request->monto;
                if ($cambio < 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El monto recibido es menor al monto a pagar',
                    ], 422);
                }
            }

            // Crear pago
            $pago = VentaPago::create([
                'venta_id' => $venta->id,
                'metodo_pago_id' => $metodoPago->id,
                'monto' => $request->monto,
                'monto_recibido' => $request->monto_recibido,
                'cambio' => $cambio,
                'estado_pago' => VentaPago::ESTADO_APROBADO,
                'notas' => $request->notas,
                'user_id' => auth()->id(),
            ]);

            // Actualizar saldo de la venta
            $venta->actualizarSaldo();

            Auditoria::registrar('registrar_pago', 'venta_pagos', $pago->id, null, $pago->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado correctamente',
                'data' => [
                    'pago' => [
                        'id' => $pago->id,
                        'monto' => $pago->monto,
                        'cambio' => $pago->cambio,
                        'estado_pago' => $pago->estado_pago,
                    ],
                    'venta' => [
                        'id' => $venta->id,
                        'total_pagado' => $venta->total_pagado,
                        'saldo_pendiente' => $venta->saldo_pendiente,
                        'estado' => $venta->estado,
                    ],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar pago: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Procesar pago con pasarela online (Mercado Pago, Stripe)
     * 
     * POST /api/admin/pagos/online
     */
    public function procesarPagoOnline(Request $request): JsonResponse
    {
        $request->validate([
            'venta_id' => 'required|exists:ventas,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'monto' => 'required|numeric|min:0.01',
            'proveedor' => 'required|in:mercadopago,stripe',
        ]);

        $venta = Venta::findOrFail($request->venta_id);
        $metodoPago = MetodoPago::findOrFail($request->metodo_pago_id);

        // Validar saldo pendiente
        if ($request->monto > $venta->saldo_pendiente) {
            return response()->json([
                'success' => false,
                'message' => 'El monto excede el saldo pendiente',
            ], 422);
        }

        // Aquí se integraría con la API de Mercado Pago o Stripe
        // Por ahora retornamos un placeholder
        return response()->json([
            'success' => false,
            'message' => 'Integración con pasarelas de pago pendiente de implementar',
            'nota' => 'Este endpoint requiere configuración de credenciales de Mercado Pago/Stripe',
        ], 501);
    }

    /**
     * Webhook de Mercado Pago
     * 
     * POST /webhooks/mercadopago
     */
    public function webhookMercadoPago(Request $request): JsonResponse
    {
        try {
            // Validar firma del webhook (implementar según documentación de Mercado Pago)
            // $signature = $request->header('x-signature');
            // if (!$this->validarFirmaMercadoPago($request->all(), $signature)) {
            //     return response()->json(['error' => 'Firma inválida'], 401);
            // }

            $payload = $request->all();
            $evento = $payload['type'] ?? null;
            $data = $payload['data'] ?? null;

            // Guardar webhook
            $webhook = WebhookPago::create([
                'venta_id' => $data['external_reference'] ?? null,
                'proveedor' => WebhookPago::PROVEEDOR_MERCADOPAGO,
                'evento_tipo' => $evento,
                'payload' => $payload,
                'procesado' => false,
                'created_at' => now(),
            ]);

            // Procesar según tipo de evento
            if ($evento === 'payment') {
                $this->procesarPagoMercadoPago($data, $webhook);
            }

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Error en webhook Mercado Pago: ' . $e->getMessage(), [
                'payload' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Error al procesar webhook',
            ], 500);
        }
    }

    /**
     * Webhook de Stripe
     * 
     * POST /webhooks/stripe
     */
    public function webhookStripe(Request $request): JsonResponse
    {
        try {
            // Validar firma del webhook (implementar según documentación de Stripe)
            // $signature = $request->header('stripe-signature');
            // if (!$this->validarFirmaStripe($request->getContent(), $signature)) {
            //     return response()->json(['error' => 'Firma inválida'], 401);
            // }

            $payload = $request->all();
            $evento = $payload['type'] ?? null;
            $data = $payload['data']['object'] ?? null;

            // Guardar webhook
            $webhook = WebhookPago::create([
                'venta_id' => $data['metadata']['venta_id'] ?? null,
                'proveedor' => WebhookPago::PROVEEDOR_STRIPE,
                'evento_tipo' => $evento,
                'payload' => $payload,
                'procesado' => false,
                'created_at' => now(),
            ]);

            // Procesar según tipo de evento
            if ($evento === 'payment_intent.succeeded') {
                $this->procesarPagoStripe($data, $webhook);
            }

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Error en webhook Stripe: ' . $e->getMessage(), [
                'payload' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Error al procesar webhook',
            ], 500);
        }
    }

    /**
     * Listar pagos de una venta
     * 
     * GET /api/admin/pagos/venta/{venta_id}
     */
    public function listarPagos(int $ventaId): JsonResponse
    {
        $venta = Venta::find($ventaId);

        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada',
            ], 404);
        }

        $pagos = VentaPago::with('metodoPago')
            ->where('venta_id', $ventaId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'venta' => [
                    'id' => $venta->id,
                    'total' => $venta->total,
                    'total_pagado' => $venta->total_pagado,
                    'saldo_pendiente' => $venta->saldo_pendiente,
                ],
                'pagos' => $pagos->map(fn($p) => [
                    'id' => $p->id,
                    'metodo_pago' => [
                        'id' => $p->metodoPago->id,
                        'nombre' => $p->metodoPago->nombre,
                    ],
                    'monto' => $p->monto,
                    'monto_recibido' => $p->monto_recibido,
                    'cambio' => $p->cambio,
                    'estado_pago' => $p->estado_pago,
                    'proveedor_pago' => $p->proveedor_pago,
                    'transaccion_id' => $p->transaccion_id,
                    'notas' => $p->notas,
                    'created_at' => $p->created_at,
                ]),
            ],
        ]);
    }

    /**
     * Reembolsar pago
     * 
     * POST /api/admin/pagos/{id}/reembolsar
     */
    public function reembolsar(int $id): JsonResponse
    {
        $pago = VentaPago::with('venta')->find($id);

        if (!$pago) {
            return response()->json([
                'success' => false,
                'message' => 'Pago no encontrado',
            ], 404);
        }

        if ($pago->estado_pago !== VentaPago::ESTADO_APROBADO) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden reembolsar pagos aprobados',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Si es pago online, procesar reembolso con la pasarela
            if ($pago->proveedor_pago && $pago->transaccion_id) {
                // Aquí se integraría con la API de la pasarela para procesar el reembolso
                // Por ahora solo marcamos como reembolsado
            }

            $pago->estado_pago = VentaPago::ESTADO_REEMBOLSADO;
            $pago->save();

            // Actualizar saldo de la venta
            $pago->venta->actualizarSaldo();

            Auditoria::registrar('reembolsar', 'venta_pagos', $pago->id, null, $pago->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago reembolsado correctamente',
                'data' => [
                    'pago_id' => $pago->id,
                    'venta' => [
                        'total_pagado' => $pago->venta->total_pagado,
                        'saldo_pendiente' => $pago->venta->saldo_pendiente,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al reembolsar pago: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Procesar pago de Mercado Pago
     */
    private function procesarPagoMercadoPago(array $data, WebhookPago $webhook): void
    {
        DB::beginTransaction();
        try {
            $paymentId = $data['id'] ?? null;
            $ventaId = $data['external_reference'] ?? null;
            $status = $data['status'] ?? null;

            if (!$ventaId) {
                throw new \Exception('No se encontró external_reference en el webhook');
            }

            $venta = Venta::find($ventaId);
            if (!$venta) {
                throw new \Exception("Venta no encontrada: {$ventaId}");
            }

            // Buscar o crear pago
            $pago = VentaPago::where('transaccion_id', $paymentId)->first();

            if (!$pago) {
                // Crear nuevo pago
                $metodoPago = MetodoPago::where('codigo', 'mercado_pago')->first();
                if (!$metodoPago) {
                    throw new \Exception('Método de pago Mercado Pago no configurado');
                }

                $monto = $data['transaction_amount'] ?? 0;

                $pago = VentaPago::create([
                    'venta_id' => $venta->id,
                    'metodo_pago_id' => $metodoPago->id,
                    'monto' => $monto,
                    'transaccion_id' => $paymentId,
                    'proveedor_pago' => VentaPago::PROVEEDOR_MERCADOPAGO,
                    'estado_pago' => $status === 'approved' ? VentaPago::ESTADO_APROBADO : VentaPago::ESTADO_PENDIENTE,
                    'metadata_pago' => $data,
                    'user_id' => auth()->id() ?? 1,
                ]);
            } else {
                // Actualizar estado del pago existente
                $pago->estado_pago = $status === 'approved' ? VentaPago::ESTADO_APROBADO : VentaPago::ESTADO_PENDIENTE;
                $pago->metadata_pago = $data;
                $pago->save();
            }

            // Actualizar saldo de la venta
            $venta->actualizarSaldo();

            // Marcar webhook como procesado
            $webhook->procesado = true;
            $webhook->respuesta = ['procesado' => true, 'pago_id' => $pago->id];
            $webhook->save();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $webhook->error_message = $e->getMessage();
            $webhook->save();
            throw $e;
        }
    }

    /**
     * Procesar pago de Stripe
     */
    private function procesarPagoStripe(array $data, WebhookPago $webhook): void
    {
        DB::beginTransaction();
        try {
            $paymentIntentId = $data['id'] ?? null;
            $ventaId = $data['metadata']['venta_id'] ?? null;
            $status = $data['status'] ?? null;

            if (!$ventaId) {
                throw new \Exception('No se encontró venta_id en metadata');
            }

            $venta = Venta::find($ventaId);
            if (!$venta) {
                throw new \Exception("Venta no encontrada: {$ventaId}");
            }

            // Buscar o crear pago
            $pago = VentaPago::where('transaccion_id', $paymentIntentId)->first();

            if (!$pago) {
                $metodoPago = MetodoPago::where('codigo', 'stripe')->first();
                if (!$metodoPago) {
                    throw new \Exception('Método de pago Stripe no configurado');
                }

                $monto = ($data['amount'] ?? 0) / 100; // Stripe usa centavos

                $pago = VentaPago::create([
                    'venta_id' => $venta->id,
                    'metodo_pago_id' => $metodoPago->id,
                    'monto' => $monto,
                    'transaccion_id' => $paymentIntentId,
                    'proveedor_pago' => VentaPago::PROVEEDOR_STRIPE,
                    'estado_pago' => $status === 'succeeded' ? VentaPago::ESTADO_APROBADO : VentaPago::ESTADO_PENDIENTE,
                    'metadata_pago' => $data,
                    'user_id' => auth()->id() ?? 1,
                ]);
            } else {
                $pago->estado_pago = $status === 'succeeded' ? VentaPago::ESTADO_APROBADO : VentaPago::ESTADO_PENDIENTE;
                $pago->metadata_pago = $data;
                $pago->save();
            }

            $venta->actualizarSaldo();

            $webhook->procesado = true;
            $webhook->respuesta = ['procesado' => true, 'pago_id' => $pago->id];
            $webhook->save();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $webhook->error_message = $e->getMessage();
            $webhook->save();
            throw $e;
        }
    }
}
