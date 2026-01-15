<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected string $fcmServerKey;
    protected string $fcmApiUrl;

    public function __construct()
    {
        $this->fcmServerKey = config('services.firebase.server_key', '');
        $this->fcmApiUrl = config('services.firebase.fcm_url', 'https://fcm.googleapis.com/fcm/send');
    }

    /**
     * Enviar push notification a un dispositivo
     */
    public function enviar(string $token, string $titulo, string $mensaje, array $data = []): array
    {
        try {
            // Si no hay configuración, simular envío (desarrollo)
            if (empty($this->fcmServerKey)) {
                Log::info("Push notification (simulada) - Título: {$titulo}, Mensaje: {$mensaje}");
                return ['success' => true, 'simulated' => true];
            }

            $payload = [
                'to' => $token,
                'notification' => [
                    'title' => $titulo,
                    'body' => $mensaje,
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data' => $data,
                'priority' => 'high',
                // Configuración específica para Android - asegura que se muestre como notificación del sistema
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'beautyspa_citas',
                        'sound' => 'default',
                        'priority' => 'high',
                        'default_sound' => true,
                        'default_vibrate_timings' => true,
                        'default_light_settings' => true,
                    ],
                ],
            ];

            Log::info("📤 Enviando push notification a Firebase", [
                'token_preview' => substr($token, 0, 20) . '...',
                'titulo' => $titulo,
                'mensaje_preview' => substr($mensaje, 0, 50),
                'fcm_url' => $this->fcmApiUrl,
                'tiene_server_key' => !empty($this->fcmServerKey),
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmApiUrl, $payload);

            $responseData = $response->json();
            
            Log::info("📥 Respuesta de Firebase", [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response' => $responseData,
            ]);

            if ($response->successful()) {
                // Verificar si el token es válido
                if (isset($responseData['results'][0]['error'])) {
                    $error = $responseData['results'][0]['error'];
                    
                    Log::warning("⚠️ Error en respuesta de Firebase", [
                        'error' => $error,
                        'token_preview' => substr($token, 0, 20) . '...',
                    ]);
                    
                    if (in_array($error, ['NotRegistered', 'InvalidRegistration'])) {
                        return [
                            'success' => false,
                            'error' => $error,
                            'invalid_token' => true,
                        ];
                    }
                    
                    return ['success' => false, 'error' => $error];
                }

                Log::info("✅ Push notification enviada exitosamente", [
                    'message_id' => $responseData['results'][0]['message_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message_id' => $responseData['results'][0]['message_id'] ?? null,
                ];
            }

            Log::error("❌ Error HTTP de Firebase", [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'error' => 'Error HTTP: ' . $response->status(),
                'response' => $responseData,
            ];
            
        } catch (\Exception $e) {
            Log::error("Error en PushNotificationService: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar push notification a múltiples dispositivos
     */
    public function enviarMultiple(array $tokens, string $titulo, string $mensaje, array $data = []): array
    {
        try {
            if (empty($this->fcmServerKey)) {
                Log::info("Push notification múltiple (simulada) a " . count($tokens) . " dispositivos");
                return ['success' => true, 'simulated' => true];
            }

            // FCM permite hasta 1000 tokens por request
            $chunks = array_chunk($tokens, 1000);
            $resultados = [];

            foreach ($chunks as $chunk) {
                $payload = [
                    'registration_ids' => $chunk,
                    'notification' => [
                        'title' => $titulo,
                        'body' => $mensaje,
                        'sound' => 'default',
                    ],
                    'data' => $data,
                    'priority' => 'high',
                    // Configuración específica para Android
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'beautyspa_citas',
                            'sound' => 'default',
                            'priority' => 'high',
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                            'default_light_settings' => true,
                        ],
                    ],
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'key=' . $this->fcmServerKey,
                    'Content-Type' => 'application/json',
                ])->post($this->fcmApiUrl, $payload);

                if ($response->successful()) {
                    $responseData = $response->json();
                    $resultados[] = [
                        'success' => $responseData['success'] ?? 0,
                        'failure' => $responseData['failure'] ?? 0,
                    ];
                }
            }

            return [
                'success' => true,
                'results' => $resultados,
            ];
            
        } catch (\Exception $e) {
            Log::error("Error en envío múltiple de push: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar a un topic
     */
    public function enviarATopic(string $topic, string $titulo, string $mensaje, array $data = []): array
    {
        try {
            if (empty($this->fcmServerKey)) {
                Log::info("Push notification a topic (simulada): {$topic}");
                return ['success' => true, 'simulated' => true];
            }

            $payload = [
                'to' => '/topics/' . $topic,
                'notification' => [
                    'title' => $titulo,
                    'body' => $mensaje,
                    'sound' => 'default',
                ],
                'data' => $data,
                'priority' => 'high',
                // Configuración específica para Android
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'beautyspa_citas',
                        'sound' => 'default',
                        'priority' => 'high',
                        'default_sound' => true,
                        'default_vibrate_timings' => true,
                        'default_light_settings' => true,
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmApiUrl, $payload);

            return [
                'success' => $response->successful(),
                'message_id' => $response->json()['message_id'] ?? null,
            ];
            
        } catch (\Exception $e) {
            Log::error("Error enviando a topic: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Suscribir dispositivo a un topic
     */
    public function suscribirATopic(string $token, string $topic): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
            ])->post("https://iid.googleapis.com/iid/v1/{$token}/rel/topics/{$topic}");

            return ['success' => $response->successful()];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Desuscribir dispositivo de un topic
     */
    public function desuscribirDeTopic(array $tokens, string $topic): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post('https://iid.googleapis.com/iid/v1:batchRemove', [
                'to' => '/topics/' . $topic,
                'registration_tokens' => $tokens,
            ]);

            return ['success' => $response->successful()];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

