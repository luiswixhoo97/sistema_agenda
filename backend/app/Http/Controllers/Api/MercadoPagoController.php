<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Models\MetodoPago;
use App\Models\MercadoPagoPago;
use App\Services\MercadoPagoService;
use App\Services\CitaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MercadoPagoController extends Controller
{
    protected MercadoPagoService $mercadoPagoService;
    protected CitaService $citaService;

    public function __construct(
        MercadoPagoService $mercadoPagoService,
        CitaService $citaService
    ) {
        $this->mercadoPagoService = $mercadoPagoService;
        $this->citaService = $citaService;
    }

    /**
     * Crear preferencia de pago para anticipo de cita
     * 
     * POST /api/publico/mercadopago/crear-preferencia
     */
    public function crearPreferencia(Request $request): JsonResponse
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'required|string|max:255',
            'payer_email' => 'nullable|email',
            'payer_name' => 'nullable|string|max:100',
            'payer_surname' => 'nullable|string|max:100',
            'external_reference' => 'required|string|max:100',
            'back_url_success' => 'nullable|url',
            'back_url_failure' => 'nullable|url',
            'back_url_pending' => 'nullable|url',
            // También aceptar los nombres cortos para compatibilidad
            'success' => 'nullable|url',
            'failure' => 'nullable|url',
            'pending' => 'nullable|url',
        ]);

        try {
            // Si no hay email, generar uno temporal basado en el external_reference
            $payerEmail = $request->payer_email;
            if (empty($payerEmail) || !filter_var($payerEmail, FILTER_VALIDATE_EMAIL)) {
                // Generar email temporal para el payer
                $payerEmail = 'cliente_' . $request->external_reference . '@temp.mercadopago';
            }

            $payer = [
                'email' => $payerEmail,
            ];

            if ($request->payer_name) {
                $payer['name'] = $request->payer_name;
            }

            if ($request->payer_surname) {
                $payer['surname'] = $request->payer_surname;
            }

            // Aceptar tanto back_url_* como los nombres cortos (success, failure, pending)
            // IMPORTANTE: success es requerido cuando se usa auto_return
            $backUrls = [];
            
            // Success es obligatorio
            $successUrl = $request->back_url_success ?? $request->success;
            if (!$successUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'back_url_success es requerido',
                ], 422);
            }
            $backUrls['success'] = $successUrl;
            
            // Failure y pending son opcionales, usar success como fallback
            $backUrls['failure'] = $request->back_url_failure ?? $request->failure ?? $successUrl;
            $backUrls['pending'] = $request->back_url_pending ?? $request->pending ?? $successUrl;

            // Log para debugging
            Log::info('Preparando preferencia de Mercado Pago', [
                'back_urls' => $backUrls,
                'has_success' => isset($backUrls['success']) && !empty($backUrls['success']),
                'success_url' => $backUrls['success'] ?? 'NO DEFINIDO',
            ]);

            $preferencia = $this->mercadoPagoService->crearPreferencia(
                $request->monto,
                $request->descripcion,
                $payer,
                $request->external_reference,
                $backUrls
            );

            if (!$preferencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la preferencia de pago',
                ], 500);
            }

            // Guardar registro del intento de pago
            MercadoPagoPago::create([
                'preference_id' => $preferencia['id'],
                'external_reference' => $request->external_reference,
                'monto' => $request->monto,
                'estado' => 'pending',
                'payer_email' => $request->payer_email,
            ]);

            return response()->json([
                'success' => true,
                'data' => $preferencia,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al crear preferencia de Mercado Pago', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Webhook para recibir notificaciones de Mercado Pago
     * 
     * POST /api/publico/mercadopago/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            Log::info('Webhook recibido de Mercado Pago', [
                'data' => $data,
            ]);

            // Mercado Pago envía diferentes tipos de notificaciones
            if (isset($data['type'])) {
                $type = $data['type'];
                $dataId = $data['data']['id'] ?? null;

                if ($type === 'payment' && $dataId) {
                    // Obtener información del pago
                    $pagoInfo = $this->mercadoPagoService->obtenerPago($dataId);

                    if ($pagoInfo) {
                        $this->procesarNotificacionPago($pagoInfo);
                    }
                }
            } elseif (isset($data['action']) && $data['action'] === 'payment.updated') {
                // Formato alternativo de notificación
                $paymentId = $data['data']['id'] ?? null;

                if ($paymentId) {
                    $pagoInfo = $this->mercadoPagoService->obtenerPago($paymentId);

                    if ($pagoInfo) {
                        $this->procesarNotificacionPago($pagoInfo);
                    }
                }
            }

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Error procesando webhook de Mercado Pago', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json(['success' => false], 500);
        }
    }

    /**
     * Verificar estado de un pago
     * 
     * GET /api/publico/mercadopago/verificar/{payment_id}
     */
    public function verificarPago(string $paymentId): JsonResponse
    {
        try {
            $pagoInfo = $this->mercadoPagoService->obtenerPago($paymentId);

            if (!$pagoInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado',
                ], 404);
            }

            // Actualizar registro local
            $registroPago = MercadoPagoPago::where('payment_id', $paymentId)
                ->orWhere('external_reference', $pagoInfo['external_reference'])
                ->first();

            if ($registroPago) {
                $registroPago->update([
                    'payment_id' => $pagoInfo['id'],
                    'estado' => $pagoInfo['status'],
                    'monto' => $pagoInfo['transaction_amount'],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $pagoInfo,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al verificar pago de Mercado Pago', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el pago',
            ], 500);
        }
    }

    /**
     * Procesar notificación de pago y actualizar estado de la cita/venta
     */
    private function procesarNotificacionPago(array $pagoInfo): void
    {
        DB::beginTransaction();
        try {
            $externalReference = $pagoInfo['external_reference'];
            $paymentId = $pagoInfo['id'];
            $status = $pagoInfo['status'];

            // Buscar o crear registro de pago
            $registroPago = MercadoPagoPago::where('external_reference', $externalReference)
                ->orWhere('payment_id', $paymentId)
                ->first();

            if (!$registroPago) {
                $registroPago = MercadoPagoPago::create([
                    'payment_id' => $paymentId,
                    'preference_id' => null,
                    'external_reference' => $externalReference,
                    'monto' => $pagoInfo['transaction_amount'],
                    'estado' => $status,
                    'payer_email' => $pagoInfo['payer']['email'] ?? null,
                ]);
            } else {
                $registroPago->update([
                    'payment_id' => $paymentId,
                    'estado' => $status,
                    'monto' => $pagoInfo['transaction_amount'],
                ]);
            }

            // Si el pago está aprobado, procesar el anticipo
            if ($status === 'approved') {
                $this->procesarAnticipoAprobado($externalReference, $pagoInfo);
            }

            DB::commit();

            Log::info('Pago procesado correctamente', [
                'payment_id' => $paymentId,
                'external_reference' => $externalReference,
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error procesando notificación de pago', [
                'pago_info' => $pagoInfo,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Procesar anticipo aprobado y actualizar estado de la cita
     */
    private function procesarAnticipoAprobado(string $externalReference, array $pagoInfo): void
    {
        // El external_reference puede tener diferentes formatos:
        // - "anticipo_{timestamp}" - Para pagos antes de agendar
        // - "cita_{id}" - Para pagos de citas ya creadas
        // - Solo número - ID de cita directo
        
        $citaId = null;
        $esAnticipoTemporal = false;

        if (str_starts_with($externalReference, 'anticipo_')) {
            // Es un anticipo temporal, se asociará cuando se agende la cita
            $esAnticipoTemporal = true;
            Log::info('Anticipo temporal aprobado (se asociará al agendar)', [
                'external_reference' => $externalReference,
                'monto' => $pagoInfo['transaction_amount'],
            ]);
            return; // Se procesará cuando se agende la cita
        } elseif (str_starts_with($externalReference, 'cita_')) {
            $citaId = (int) str_replace('cita_', '', $externalReference);
        } else {
            // Intentar parsear como número
            $citaId = is_numeric($externalReference) ? (int) $externalReference : null;
        }

        if ($citaId) {
            $cita = Cita::with('venta')->find($citaId);

            if ($cita && $cita->venta) {
                // Buscar método de pago de Mercado Pago
                $metodoPago = MetodoPago::where('codigo', 'mercado_pago')
                    ->orWhere('codigo', 'mercadopago')
                    ->first();

                if (!$metodoPago) {
                    Log::warning('Método de pago Mercado Pago no encontrado', [
                        'cita_id' => $citaId,
                    ]);
                    return;
                }

                // Verificar si ya existe un pago con este transaction_id
                $pagoExistente = VentaPago::where('transaccion_id', $pagoInfo['id'])->first();

                if (!$pagoExistente) {
                    // Crear registro de pago en venta_pagos
                    VentaPago::create([
                        'venta_id' => $cita->venta->id,
                        'metodo_pago_id' => $metodoPago->id,
                        'monto' => $pagoInfo['transaction_amount'],
                        'transaccion_id' => (string) $pagoInfo['id'],
                        'proveedor_pago' => VentaPago::PROVEEDOR_MERCADOPAGO,
                        'estado_pago' => VentaPago::ESTADO_APROBADO,
                        'metadata_pago' => $pagoInfo,
                        'notas' => 'Anticipo pagado con Mercado Pago',
                        'user_id' => 1, // Sistema
                    ]);

                    // Actualizar saldo de la venta
                    $cita->venta->actualizarSaldo();

                    Log::info('Anticipo registrado correctamente en venta', [
                        'cita_id' => $citaId,
                        'venta_id' => $cita->venta->id,
                        'monto' => $pagoInfo['transaction_amount'],
                    ]);
                }
            }
        }
    }
}
