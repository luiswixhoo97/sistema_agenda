<?php

namespace App\Services;

use App\Models\Cita;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class QrService
{
    /**
     * Generar código QR para una cita
     * 
     * @param Cita $cita
     * @return string|null URL del QR (data URI en formato PNG) o null si hay error
     */
    public function generarQrCita(Cita $cita): ?string
    {
        try {
            // Asegurar que la cita tenga un token QR
            if (!$cita->token_qr) {
                Log::warning("Cita sin token QR, no se puede generar código", [
                    'cita_id' => $cita->id,
                ]);
                return null;
            }

            // Generar URL para escanear el QR
            // El token se puede escanear desde la app del empleado/admin
            $urlQr = $this->generarUrlQr($cita->token_qr);

            // Generar QR code como PNG (base64 data URI)
            // Usar formato PNG porque WhatsApp no acepta SVG
            // Nota: Requiere ext-imagick para generar PNG
            try {
                $qrPng = QrCode::format('png')
                    ->size(300)
                    ->margin(2)
                    ->errorCorrection('H') // Alto nivel de corrección de errores
                    ->generate($urlQr);

                // Convertir a data URI
                $dataUri = 'data:image/png;base64,' . base64_encode($qrPng);
            } catch (\Exception $e) {
                // Si falla PNG (probablemente falta imagick), intentar SVG y convertir
                Log::warning("No se pudo generar PNG, intentando SVG: " . $e->getMessage());
                
                // Generar como SVG y convertir a PNG usando GD (si está disponible)
                // Por ahora, retornar null si no se puede generar PNG
                // El sistema funcionará sin QR en este caso
                return null;
            }

            Log::info("✅ QR generado para cita", [
                'cita_id' => $cita->id,
                'token_qr' => substr($cita->token_qr, 0, 10) . '...',
                'qr_size' => strlen($qrPng),
            ]);

            return $dataUri;

        } catch (\Exception $e) {
            Log::error("Error al generar QR para cita: " . $e->getMessage(), [
                'cita_id' => $cita->id ?? null,
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
            
            return null;
        }
    }

    /**
     * Generar URL para el código QR
     * 
     * @param string $token
     * @return string
     */
    protected function generarUrlQr(string $token): string
    {
        // Opción 1: URL completa para escanear (requiere que la app maneje deep links)
        // $appUrl = config('app.url');
        // return "{$appUrl}/api/publico/citas/scan-qr/{$token}";

        // Opción 2: Solo el token (la app debe tener lógica para escanearlo)
        // Por ahora, retornamos solo el token ya que el endpoint de scan requiere autenticación
        // La app móvil puede manejar el token directamente
        return $token;
    }

    /**
     * Generar QR code simple (para otros usos)
     * 
     * @param string $contenido
     * @param int $tamaño
     * @return string|null Data URI del QR o null si hay error
     */
    public function generarQr(string $contenido, int $tamaño = 300): ?string
    {
        try {
            $qrPng = QrCode::format('png')
                ->size($tamaño)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($contenido);

            return 'data:image/png;base64,' . base64_encode($qrPng);

        } catch (\Exception $e) {
            Log::error("Error al generar QR: " . $e->getMessage());
            return null;
        }
    }
}
