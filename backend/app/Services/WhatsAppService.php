<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $apiToken;
    protected string $phoneNumberId;

    public function __construct()
    {
        // Configuración para WhatsApp Cloud API (Meta)
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->apiToken = config('services.whatsapp.token', '');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
    }

    /**
     * Enviar mensaje de WhatsApp
     */
    public function enviarMensaje(string $telefono, string $mensaje, string $tipoPlantilla = 'text'): array
    {
        try {
            // Formatear número de teléfono (quitar caracteres especiales)
            $telefono = $this->formatearTelefono($telefono);

            // Si no hay configuración, simular envío (desarrollo)
            if (empty($this->apiToken) || empty($this->phoneNumberId)) {
                Log::info("WhatsApp (simulado) a {$telefono}: {$mensaje}");
                return ['success' => true, 'simulated' => true];
            }

            // Determinar si usar plantilla o mensaje de texto
            if ($this->requierePlantilla($tipoPlantilla)) {
                return $this->enviarPlantilla($telefono, $tipoPlantilla, $mensaje);
            }

            return $this->enviarTexto($telefono, $mensaje);
            
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
        $response = Http::withToken($this->apiToken)
            ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $telefono,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $mensaje,
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'message_id' => $data['messages'][0]['id'] ?? null,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json()['error']['message'] ?? 'Error desconocido',
            'status_code' => $response->status(),
        ];
    }

    /**
     * Enviar mensaje usando plantilla aprobada
     */
    protected function enviarPlantilla(string $telefono, string $nombrePlantilla, string $mensaje): array
    {
        // Las plantillas de WhatsApp Business requieren aprobación
        // Aquí se mapean los tipos internos a nombres de plantillas aprobadas
        $plantillasWhatsApp = $this->obtenerConfigPlantilla($nombrePlantilla);

        $response = Http::withToken($this->apiToken)
            ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $telefono,
                'type' => 'template',
                'template' => [
                    'name' => $plantillasWhatsApp['name'],
                    'language' => ['code' => 'es'],
                    'components' => $plantillasWhatsApp['components'] ?? [],
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'message_id' => $data['messages'][0]['id'] ?? null,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json()['error']['message'] ?? 'Error desconocido',
            'status_code' => $response->status(),
        ];
    }

    /**
     * Verificar si el tipo requiere plantilla de WhatsApp
     */
    protected function requierePlantilla(string $tipo): bool
    {
        // WhatsApp Business API requiere plantillas aprobadas para iniciar conversaciones
        // Los mensajes de texto solo se pueden enviar dentro de ventana de 24h
        $tiposPlantilla = ['confirmacion_cita', 'recordatorio_cita', 'cancelacion_cita', 'otp'];
        return in_array($tipo, $tiposPlantilla);
    }

    /**
     * Obtener configuración de plantilla de WhatsApp
     */
    protected function obtenerConfigPlantilla(string $tipo): array
    {
        // Estas plantillas deben estar aprobadas en el Business Manager de Meta
        $plantillas = [
            'confirmacion_cita' => [
                'name' => 'cita_confirmada',
                'components' => [],
            ],
            'recordatorio_cita' => [
                'name' => 'recordatorio_cita',
                'components' => [],
            ],
            'cancelacion_cita' => [
                'name' => 'cita_cancelada',
                'components' => [],
            ],
            'otp' => [
                'name' => 'codigo_verificacion',
                'components' => [],
            ],
        ];

        return $plantillas[$tipo] ?? ['name' => 'mensaje_general', 'components' => []];
    }

    /**
     * Formatear número de teléfono para WhatsApp
     */
    protected function formatearTelefono(string $telefono): string
    {
        // Remover caracteres no numéricos
        $telefono = preg_replace('/[^0-9]/', '', $telefono);
        
        // Agregar código de país si no lo tiene (asumiendo México +52)
        if (strlen($telefono) === 10) {
            $telefono = '52' . $telefono;
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

