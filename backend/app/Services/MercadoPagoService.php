<?php

namespace App\Services;

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Exceptions\MPApiException;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    private PreferenceClient $preferenceClient;
    private PaymentClient $paymentClient;

    public function __construct()
    {
        // Obtener access token desde configuración o .env
        $accessToken = config('services.mercadopago.access_token') 
            ?? env('MERCADOPAGO_ACCESS_TOKEN');
        
        if (!$accessToken) {
            throw new \Exception('Mercado Pago access token no configurado');
        }

        MercadoPagoConfig::setAccessToken($accessToken);
        
        // Configurar entorno según APP_ENV
        if (config('app.env') === 'local' || config('app.debug')) {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
        } else {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);
        }

        $this->preferenceClient = new PreferenceClient();
        $this->paymentClient = new PaymentClient();
    }

    /**
     * Crear preferencia de pago para Checkout Pro
     * 
     * @param float $monto Monto a pagar
     * @param string $descripcion Descripción del pago
     * @param array $payer Datos del pagador
     * @param string $externalReference Referencia externa (ID de cita o venta)
     * @param array $backUrls URLs de retorno
     * @return array|null
     */
    public function crearPreferencia(
        float $monto,
        string $descripcion,
        array $payer,
        string $externalReference,
        array $backUrls = []
    ): ?array {
        try {
            // Configurar URLs de retorno por defecto si no se proporcionan
            $defaultBackUrls = [
                'success' => url('/pago/exito'),
                'failure' => url('/pago/fallo'),
                'pending' => url('/pago/pendiente'),
            ];

            // Asegurar que siempre haya al menos 'success' definido y que sea una URL válida
            if (empty($backUrls) || !isset($backUrls['success']) || empty($backUrls['success'])) {
                $backUrls = array_merge($defaultBackUrls, $backUrls);
            }
            
            // Asegurar que todas las URLs estén definidas y sean válidas
            if (empty($backUrls['success'])) {
                throw new \Exception('back_urls.success debe ser una URL válida');
            }
            
            // Asegurar que failure y pending también estén definidos
            if (!isset($backUrls['failure']) || empty($backUrls['failure'])) {
                $backUrls['failure'] = $backUrls['success'];
            }
            if (!isset($backUrls['pending']) || empty($backUrls['pending'])) {
                $backUrls['pending'] = $backUrls['success'];
            }
            
            // Validar que todas las URLs sean válidas
            foreach ($backUrls as $key => $url) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    Log::warning("URL de retorno inválida para {$key}: {$url}");
                    // Usar la URL de success como fallback
                    $backUrls[$key] = $backUrls['success'];
                }
            }

            // Validar que back_urls tenga al menos 'success'
            if (empty($backUrls['success'])) {
                Log::error('back_urls.success no está definido', [
                    'backUrls' => $backUrls,
                    'backUrls_type' => gettype($backUrls),
                    'backUrls_keys' => array_keys($backUrls),
                ]);
                throw new \Exception('back_urls.success es requerido');
            }

            // Asegurar que back_urls tenga la estructura exacta que espera Mercado Pago
            $backUrlsFormatted = [
                'success' => (string) $backUrls['success'],
                'failure' => (string) ($backUrls['failure'] ?? $backUrls['success']),
                'pending' => (string) ($backUrls['pending'] ?? $backUrls['success']),
            ];

            // Crear items para la preferencia
            $items = [
                [
                    'id' => $externalReference,
                    'title' => $descripcion,
                    'description' => $descripcion,
                    'quantity' => 1,
                    'currency_id' => 'MXN', // Cambiar según tu moneda
                    'unit_price' => $monto,
                ]
            ];

            // Configurar métodos de pago
            $paymentMethods = [
                'excluded_payment_methods' => [],
                'excluded_payment_types' => [],
                'installments' => 12, // Máximo de cuotas
                'default_installments' => 1,
            ];
            
            // Log detallado del request completo antes de enviarlo
            Log::info('Request completo para Mercado Pago', [
                'back_urls_formatted' => $backUrlsFormatted,
                'back_urls_type' => gettype($backUrlsFormatted),
                'back_urls_keys' => array_keys($backUrlsFormatted),
                'back_urls_success_exists' => isset($backUrlsFormatted['success']),
                'back_urls_success_value' => $backUrlsFormatted['success'] ?? 'NO EXISTE',
                'back_urls_success_empty' => empty($backUrlsFormatted['success']),
            ]);

            // Crear request de preferencia
            // Construir el array en el orden que espera el SDK
            $request = [
                'items' => $items,
                'payer' => $payer,
                'back_urls' => $backUrlsFormatted,
                'payment_methods' => $paymentMethods,
                'external_reference' => $externalReference,
                'statement_descriptor' => config('app.name', 'Sistema Agenda'),
                'expires' => false, // No expira
            ];
            
            // Solo agregar auto_return si back_urls.success está definido
            // Según la documentación, auto_return requiere back_urls.success
            // TEMPORAL: Comentar auto_return para debug
            // if (!empty($backUrlsFormatted['success'])) {
            //     $request['auto_return'] = 'approved';
            // }
            
            // Por ahora, no usar auto_return hasta resolver el problema
            // El usuario puede usar el botón "Volver al sitio" en Mercado Pago
            
            // Validación final antes de enviar
            if (!isset($request['back_urls']['success']) || empty($request['back_urls']['success'])) {
                Log::error('ERROR CRÍTICO: back_urls.success no está en el request final', [
                    'request_back_urls' => $request['back_urls'] ?? 'NO EXISTE',
                    'request_keys' => array_keys($request),
                ]);
                throw new \Exception('back_urls.success no está definido en el request final');
            }
            
            // Log del request completo serializado para verificar formato
            Log::info('Request serializado para Mercado Pago', [
                'request_json' => json_encode($request, JSON_PRETTY_PRINT),
                'back_urls_in_request' => $request['back_urls'] ?? 'NO EXISTE',
            ]);

            // Log del request antes de enviarlo (sin datos sensibles)
            Log::info('Creando preferencia de Mercado Pago', [
                'back_urls' => $backUrlsFormatted,
                'external_reference' => $externalReference,
                'monto' => $monto,
                'has_success' => !empty($backUrlsFormatted['success']),
                'success_url' => $backUrlsFormatted['success'],
            ]);

            // Crear preferencia
            // Intentar crear la preferencia
            try {
                $preference = $this->preferenceClient->create($request);
            } catch (MPApiException $e) {
                // Log detallado del error
                Log::error('Error detallado de Mercado Pago API', [
                    'error_message' => $e->getMessage(),
                    'status_code' => $e->getApiResponse()->getStatusCode(),
                    'content' => $e->getApiResponse()->getContent(),
                    'request_back_urls' => $request['back_urls'] ?? 'NO EXISTE',
                    'request_auto_return' => $request['auto_return'] ?? 'NO EXISTE',
                ]);
                throw $e;
            }

            Log::info('Preferencia de Mercado Pago creada', [
                'preference_id' => $preference->id,
                'external_reference' => $externalReference,
                'monto' => $monto,
            ]);

            return [
                'id' => $preference->id,
                'init_point' => $preference->init_point,
                'sandbox_init_point' => $preference->sandbox_init_point ?? null,
            ];

        } catch (MPApiException $e) {
            Log::error('Error al crear preferencia de Mercado Pago', [
                'error' => $e->getMessage(),
                'status_code' => $e->getApiResponse()->getStatusCode(),
                'content' => $e->getApiResponse()->getContent(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Excepción al crear preferencia de Mercado Pago', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtener información de un pago por ID
     * 
     * @param int|string $paymentId
     * @return array|null
     */
    public function obtenerPago($paymentId): ?array
    {
        try {
            $payment = $this->paymentClient->get($paymentId);

            return [
                'id' => $payment->id,
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'transaction_amount' => $payment->transaction_amount,
                'currency_id' => $payment->currency_id,
                'payment_method_id' => $payment->payment_method_id,
                'payment_type_id' => $payment->payment_type_id,
                'date_created' => $payment->date_created,
                'date_approved' => $payment->date_approved,
                'external_reference' => $payment->external_reference,
                'payer' => [
                    'email' => $payment->payer->email ?? null,
                    'identification' => [
                        'type' => $payment->payer->identification->type ?? null,
                        'number' => $payment->payer->identification->number ?? null,
                    ],
                ],
            ];

        } catch (MPApiException $e) {
            Log::error('Error al obtener pago de Mercado Pago', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'status_code' => $e->getApiResponse()->getStatusCode(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Excepción al obtener pago de Mercado Pago', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verificar si un pago está aprobado
     * 
     * @param int|string $paymentId
     * @return bool
     */
    public function pagoAprobado($paymentId): bool
    {
        $pago = $this->obtenerPago($paymentId);
        
        if (!$pago) {
            return false;
        }

        return $pago['status'] === 'approved';
    }

    /**
     * Verificar si un pago está pendiente
     * 
     * @param int|string $paymentId
     * @return bool
     */
    public function pagoPendiente($paymentId): bool
    {
        $pago = $this->obtenerPago($paymentId);
        
        if (!$pago) {
            return false;
        }

        return $pago['status'] === 'pending';
    }

    /**
     * Verificar si un pago fue rechazado
     * 
     * @param int|string $paymentId
     * @return bool
     */
    public function pagoRechazado($paymentId): bool
    {
        $pago = $this->obtenerPago($paymentId);
        
        if (!$pago) {
            return false;
        }

        return in_array($pago['status'], ['rejected', 'cancelled', 'refunded', 'charged_back']);
    }
}
