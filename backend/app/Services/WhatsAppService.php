<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use WasenderApi\WasenderClient;
use WasenderApi\Exceptions\WasenderApiException;

class WhatsAppService
{
    protected WasenderClient $client;

    public function __construct()
    {
        // Usar el SDK oficial de WasenderAPI
        $this->client = new WasenderClient();
    }

    /**
     * Enviar mensaje de WhatsApp
     */
    public function enviarMensaje(string $telefono, string $mensaje, string $tipoPlantilla = 'text', ?string $imagenUrl = null): array
    {
        try {
            // Formatear número de teléfono con +
            $telefonoFormateado = '+' . $this->formatearTelefono($telefono);

            // Si hay imagen, enviar mensaje con imagen (el mensaje va como caption)
            if ($imagenUrl) {
                return $this->enviarImagen($telefonoFormateado, $imagenUrl, $mensaje);
            }

            // Enviar mensaje de texto simple
            return $this->enviarTexto($telefonoFormateado, $mensaje);
            
        } catch (WasenderApiException $e) {
            Log::error("Error WasenderAPI: " . $e->getMessage(), [
                'code' => $e->getCode(),
                'response' => $e->getResponse(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => $e->getCode(),
            ];
        } catch (\Exception $e) {
            Log::error("Error en WhatsAppService: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar mensaje de texto simple
     */
    protected function enviarTexto(string $telefono, string $mensaje): array
    {
        Log::info("📤 Enviando WhatsApp texto", [
            'to' => $telefono,
            'text_length' => strlen($mensaje),
        ]);

        // En desarrollo, usar Http directamente con verificación SSL deshabilitada
        if (app()->environment('local')) {
            return $this->enviarTextoDirecto($telefono, $mensaje);
        }

        $response = $this->client->sendText($telefono, $mensaje);

        Log::info("✅ WhatsApp enviado", [
            'to' => $telefono,
            'response' => $response,
        ]);

        return [
            'success' => true,
            'message_id' => $response['id'] ?? $response['messageId'] ?? null,
        ];
    }

    /**
     * Enviar mensaje con imagen
     */
    protected function enviarImagen(string $telefono, string $imagenUrl, string $caption = ''): array
    {
        Log::info("📤 Enviando WhatsApp con imagen", [
            'to' => $telefono,
            'image_url_type' => str_starts_with($imagenUrl, 'data:image/') ? 'base64' : 'url',
            'caption_length' => strlen($caption),
        ]);

        // En desarrollo, usar Http directamente con verificación SSL deshabilitada
        if (app()->environment('local')) {
            return $this->enviarImagenDirecto($telefono, $imagenUrl, $caption);
        }

        // Si es base64, primero subir a WasenderAPI
        if (str_starts_with($imagenUrl, 'data:image/')) {
            $imagenUrl = $this->subirImagenBase64($imagenUrl);
            if (!$imagenUrl) {
                throw new \Exception('No se pudo subir la imagen a WasenderAPI');
            }
        }

        // El SDK usa 'text' para el caption en imágenes
        $response = $this->client->sendImage($telefono, $imagenUrl, $caption);

        Log::info("✅ WhatsApp con imagen enviado", [
            'to' => $telefono,
            'response' => $response,
        ]);

        return [
            'success' => true,
            'message_id' => $response['id'] ?? $response['messageId'] ?? null,
        ];
    }

    /**
     * Enviar texto directamente usando Http (para desarrollo local)
     */
    protected function enviarTextoDirecto(string $telefono, string $mensaje): array
    {
        $apiKey = config('wasenderapi.api_key', env('WASENDERAPI_API_KEY'));
        $baseUrl = rtrim(config('wasenderapi.base_url', env('WASENDERAPI_BASE_URL', 'https://www.wasenderapi.com/api')), '/');

        $response = Http::withoutVerifying()
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
                'User-Agent' => 'wasenderapi-laravel-sdk',
            ])
            ->post("{$baseUrl}/send-message", [
                'to' => $telefono,
                'text' => $mensaje,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Wasender API error: ' . $response->body(), $response->status());
        }

        $data = $response->json();
        Log::info("✅ WhatsApp enviado", [
            'to' => $telefono,
            'response' => $data,
        ]);

        return [
            'success' => true,
            'message_id' => $data['id'] ?? $data['messageId'] ?? null,
        ];
    }

    /**
     * Enviar imagen directamente usando Http (para desarrollo local)
     */
    protected function enviarImagenDirecto(string $telefono, string $imagenUrl, string $caption = ''): array
    {
        $apiKey = config('wasenderapi.api_key', env('WASENDERAPI_API_KEY'));
        $baseUrl = rtrim(config('wasenderapi.base_url', env('WASENDERAPI_BASE_URL', 'https://www.wasenderapi.com/api')), '/');

        // Si la imagen es base64, subirla primero usando uploadMediaFile del SDK
        if (str_starts_with($imagenUrl, 'data:image/')) {
            $imagenUrl = $this->subirImagenBase64Directo($imagenUrl);
            if (!$imagenUrl) {
                throw new \Exception('No se pudo subir la imagen a WasenderAPI');
            }
        }
        
        // Ahora enviar el mensaje con la URL de la imagen
        $payload = [
            'to' => $telefono,
            'imageUrl' => $imagenUrl,
        ];
        
        if ($caption) {
            $payload['text'] = $caption;
        }

        Log::info("📤 Enviando mensaje con imagen a WasenderAPI", [
            'to' => $telefono,
            'imageUrl' => substr($imagenUrl, 0, 100) . '...',
            'has_caption' => !empty($caption),
        ]);

        $response = Http::withoutVerifying()
            ->withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
                'User-Agent' => 'wasenderapi-laravel-sdk',
            ])
            ->post("{$baseUrl}/send-message", $payload);

        if (!$response->successful()) {
            Log::error("Error WasenderAPI enviando imagen", [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => array_merge($payload, ['imageUrl' => '[TRUNCATED]']),
            ]);
            throw new \Exception('Wasender API error: ' . $response->body(), $response->status());
        }

        $data = $response->json();
        Log::info("✅ WhatsApp con imagen enviado", [
            'to' => $telefono,
            'response' => $data,
        ]);

        return [
            'success' => true,
            'message_id' => $data['id'] ?? $data['messageId'] ?? $data['data']['msgId'] ?? null,
        ];
    }

    /**
     * Subir imagen base64 a WasenderAPI usando el SDK
     * @param string $base64DataUri Data URI completo (data:image/png;base64,...)
     * @return string|null URL de la imagen subida
     */
    protected function subirImagenBase64(string $base64DataUri): ?string
    {
        try {
            // Extraer el base64 y mimetype del data URI
            $parts = explode(',', $base64DataUri, 2);
            if (count($parts) !== 2) {
                Log::error("Data URI inválido");
                return null;
            }

            $base64 = $parts[1];
            $mimeType = 'image/png'; // Por defecto
            
            if (str_contains($parts[0], 'png')) {
                $mimeType = 'image/png';
            } elseif (str_contains($parts[0], 'jpg') || str_contains($parts[0], 'jpeg')) {
                $mimeType = 'image/jpeg';
            }

            Log::info("📤 Subiendo imagen a WasenderAPI via SDK", [
                'mime_type' => $mimeType,
                'base64_length' => strlen($base64),
            ]);

            // Usar el método del SDK
            $result = $this->client->uploadMediaFile($base64, $mimeType);

            // Obtener URL de la respuesta (WasenderAPI usa 'publicUrl')
            $url = $result['publicUrl'] ?? $result['url'] ?? $result['data']['url'] ?? $result['mediaUrl'] ?? $result['data']['mediaUrl'] ?? null;

            if ($url) {
                Log::info("✅ Imagen subida exitosamente", ['url' => $url]);
            } else {
                Log::error("Respuesta de upload no contiene URL", ['response' => $result]);
            }

            return $url;

        } catch (\Exception $e) {
            Log::error("Error subiendo imagen: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Subir imagen base64 a WasenderAPI directamente (para desarrollo local)
     * @param string $base64DataUri Data URI completo (data:image/png;base64,...)
     * @return string|null URL de la imagen subida
     */
    protected function subirImagenBase64Directo(string $base64DataUri): ?string
    {
        try {
            $apiKey = config('wasenderapi.api_key', env('WASENDERAPI_API_KEY'));
            $baseUrl = rtrim(config('wasenderapi.base_url', env('WASENDERAPI_BASE_URL', 'https://www.wasenderapi.com/api')), '/');

            // Extraer el base64 y mimetype del data URI
            $parts = explode(',', $base64DataUri, 2);
            if (count($parts) !== 2) {
                Log::error("Data URI inválido");
                return null;
            }

            $base64 = $parts[1];
            $mimeType = 'image/png'; // Por defecto
            
            if (str_contains($parts[0], 'png')) {
                $mimeType = 'image/png';
            } elseif (str_contains($parts[0], 'jpg') || str_contains($parts[0], 'jpeg')) {
                $mimeType = 'image/jpeg';
            }

            Log::info("📤 Subiendo imagen a WasenderAPI (directo)", [
                'mime_type' => $mimeType,
                'base64_length' => strlen($base64),
            ]);

            // El SDK usa el endpoint /upload con el base64 en el body
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                    'User-Agent' => 'wasenderapi-laravel-sdk',
                ])
                ->post("{$baseUrl}/upload", [
                    'base64' => $base64,
                    'mimetype' => $mimeType,
                ]);

            if (!$response->successful()) {
                Log::error("Error subiendo imagen a WasenderAPI", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $result = $response->json();

            // Obtener URL de la respuesta (WasenderAPI usa 'publicUrl')
            $url = $result['publicUrl'] ?? $result['url'] ?? $result['data']['url'] ?? $result['mediaUrl'] ?? $result['data']['mediaUrl'] ?? null;

            if ($url) {
                Log::info("✅ Imagen subida exitosamente", ['url' => $url]);
            } else {
                Log::error("Respuesta de upload no contiene URL", ['response' => $result]);
            }

            return $url;

        } catch (\Exception $e) {
            Log::error("Error subiendo imagen: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Formatear número de teléfono para WhatsApp
     * Asegura que siempre tenga el código de país de México (+52)
     */
    protected function formatearTelefono(string $telefono): string
    {
        // Remover caracteres no numéricos y el + si existe
        $telefono = preg_replace('/[^0-9]/', '', $telefono);
        
        // Si ya tiene código de país de México (52) al inicio, mantenerlo
        if (strlen($telefono) > 10 && substr($telefono, 0, 2) === '52') {
            return $telefono;
        }
        
        // Si tiene 10 dígitos (número local de México), agregar código de país 52
        if (strlen($telefono) === 10) {
            return '52' . $telefono;
        }
        
        // Si tiene menos de 10 dígitos, asumir que es número local y agregar 52
        if (strlen($telefono) < 10) {
            return '52' . $telefono;
        }
        
        // Si tiene más de 10 dígitos pero no empieza con 52, tomar últimos 10 y agregar 52
        if (strlen($telefono) > 10 && substr($telefono, 0, 2) !== '52') {
            // Tomar últimos 10 dígitos (número local) y agregar código de país 52
            return '52' . substr($telefono, -10);
        }
        
        return $telefono;
    }

    /**
     * Verificar estado de webhook
     */
    public function verificarWebhook(string $mode, string $token, string $challenge): ?string
    {
        $verifyToken = config('services.whatsapp.verify_token', '');
        
        if ($mode === 'subscribe' && $token === $verifyToken) {
            return $challenge;
        }
        
        return null;
    }

    /**
     * Procesar webhook de WhatsApp
     */
    public function procesarWebhook(array $payload): void
    {
        // Procesar mensajes entrantes, estados de entrega, etc.
        Log::info('WhatsApp Webhook recibido', $payload);

        // Aquí se pueden manejar:
        // - Confirmaciones de lectura
        // - Respuestas de clientes
        // - Estados de entrega
    }
}
