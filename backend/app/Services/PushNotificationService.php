<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PushNotificationService
{
    protected string $projectId;
    protected ?string $credentialsPath;
    protected string $fcmApiUrl;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', 'com-beautyspa-app');
        $this->credentialsPath = config('services.firebase.credentials_path');
        $this->fcmApiUrl = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    /**
     * Obtener Access Token de Google OAuth2 para FCM v1
     */
    protected function getAccessToken(): ?string
    {
        // Intentar obtener del cache primero (tokens duran 1 hora)
        $cachedToken = Cache::get('fcm_access_token');
        if ($cachedToken) {
            return $cachedToken;
        }

        try {
            // Ruta al archivo de credenciales
            $credentialsFile = $this->credentialsPath ?? base_path('firebase-credentials.json');
            
            if (!file_exists($credentialsFile)) {
                Log::warning("⚠️ Archivo de credenciales Firebase no encontrado: {$credentialsFile}");
                return null;
            }

            $credentials = json_decode(file_get_contents($credentialsFile), true);
            
            if (!$credentials) {
                Log::error("❌ Error al leer credenciales de Firebase");
                return null;
            }

            // Crear JWT para autenticación
            $now = time();
            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT',
            ];
            
            $payload = [
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $headerEncoded = $this->base64UrlEncode(json_encode($header));
            $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
            
            $signatureInput = $headerEncoded . '.' . $payloadEncoded;
            
            // Firmar con la clave privada
            $privateKey = openssl_pkey_get_private($credentials['private_key']);
            openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
            $signatureEncoded = $this->base64UrlEncode($signature);
            
            $jwt = $signatureInput . '.' . $signatureEncoded;

            // Obtener access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;
                
                // Guardar en cache (restar 5 minutos para renovar antes de expirar)
                Cache::put('fcm_access_token', $accessToken, $expiresIn - 300);
                
                Log::info("✅ Access token de FCM obtenido correctamente");
                return $accessToken;
            }

            Log::error("❌ Error obteniendo access token de FCM", [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return null;
            
        } catch (\Exception $e) {
            Log::error("❌ Excepción obteniendo access token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Codificar en Base64 URL-safe
     */
    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Enviar push notification a un dispositivo usando FCM v1 API
     */
    public function enviar(string $token, string $titulo, string $mensaje, array $data = []): array
    {
        try {
            $accessToken = $this->getAccessToken();
            
            // Si no hay credenciales configuradas, simular envío (desarrollo)
            if (!$accessToken) {
                Log::info("📱 Push notification (simulada) - Título: {$titulo}, Mensaje: " . substr($mensaje, 0, 50));
                return ['success' => true, 'simulated' => true];
            }

            // Generar tag único para evitar que Android colapse las notificaciones
            $uniqueTag = 'notif_' . uniqid() . '_' . time();
            
            // Agregar notification_id único al data
            $data['notification_tag'] = $uniqueTag;
            
            // Payload para FCM v1 API
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $titulo,
                        'body' => $mensaje,
                    ],
                    'data' => array_map('strval', $data), // FCM v1 requiere que data sea strings
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'beautyspa_citas',
                            'sound' => 'default',
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                            'default_light_settings' => true,
                            'tag' => $uniqueTag, // Tag único para que no se colapsen las notificaciones
                            'notification_count' => 1,
                        ],
                    ],
                ],
            ];

            Log::info("📤 Enviando push notification a FCM v1", [
                'token_preview' => substr($token, 0, 20) . '...',
                'titulo' => $titulo,
                'mensaje_preview' => substr($mensaje, 0, 50),
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->fcmApiUrl, $payload);

            $responseData = $response->json();
            
            Log::info("📥 Respuesta de FCM v1", [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response' => $responseData,
            ]);

            if ($response->successful()) {
                Log::info("✅ Push notification enviada exitosamente", [
                    'name' => $responseData['name'] ?? null,
                ]);

                return [
                    'success' => true,
                    'message_id' => $responseData['name'] ?? null,
                ];
            }

            // Manejar errores específicos
            $error = $responseData['error']['message'] ?? 'Error desconocido';
            $errorCode = $responseData['error']['details'][0]['errorCode'] ?? null;
            
            Log::error("❌ Error de FCM v1", [
                'status' => $response->status(),
                'error' => $error,
                'error_code' => $errorCode,
            ]);

            // Token inválido o no registrado
            if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                return [
                    'success' => false,
                    'error' => $error,
                    'invalid_token' => true,
                ];
            }

            return [
                'success' => false,
                'error' => $error,
                'response' => $responseData,
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Excepción en PushNotificationService: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar push notification a múltiples dispositivos
     * FCM v1 no soporta envío masivo directo, enviamos uno por uno
     */
    public function enviarMultiple(array $tokens, string $titulo, string $mensaje, array $data = []): array
    {
        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                Log::info("📱 Push notification múltiple (simulada) a " . count($tokens) . " dispositivos");
                return ['success' => true, 'simulated' => true];
            }

            $resultados = [
                'success' => 0,
                'failure' => 0,
                'invalid_tokens' => [],
            ];

            foreach ($tokens as $token) {
                $result = $this->enviar($token, $titulo, $mensaje, $data);
                
                if ($result['success']) {
                    $resultados['success']++;
                } else {
                    $resultados['failure']++;
                    if ($result['invalid_token'] ?? false) {
                        $resultados['invalid_tokens'][] = $token;
                    }
                }
            }

            return [
                'success' => true,
                'results' => $resultados,
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Error en envío múltiple de push: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar a un topic
     */
    public function enviarATopic(string $topic, string $titulo, string $mensaje, array $data = []): array
    {
        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                Log::info("📱 Push notification a topic (simulada): {$topic}");
                return ['success' => true, 'simulated' => true];
            }

            // Generar tag único
            $uniqueTag = 'topic_' . uniqid() . '_' . time();
            $data['notification_tag'] = $uniqueTag;

            $payload = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $titulo,
                        'body' => $mensaje,
                    ],
                    'data' => array_map('strval', $data),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'channel_id' => 'beautyspa_citas',
                            'sound' => 'default',
                            'tag' => $uniqueTag,
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                            'default_light_settings' => true,
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->fcmApiUrl, $payload);

            return [
                'success' => $response->successful(),
                'message_id' => $response->json()['name'] ?? null,
            ];
            
        } catch (\Exception $e) {
            Log::error("❌ Error enviando a topic: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Suscribir dispositivo a un topic (usa Instance ID API)
     */
    public function suscribirATopic(string $token, string $topic): array
    {
        try {
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                return ['success' => true, 'simulated' => true];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
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
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                return ['success' => true, 'simulated' => true];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
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

