<?php

namespace App\Jobs;

use App\Models\Notificacion;
use App\Models\Dispositivo;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 15;

    protected string $token;
    protected string $titulo;
    protected string $mensaje;
    protected array $data;
    protected ?int $notificacionId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $token, 
        string $titulo, 
        string $mensaje,
        array $data = [],
        ?int $notificacionId = null
    ) {
        $this->token = $token;
        $this->titulo = $titulo;
        $this->mensaje = $mensaje;
        $this->data = $data;
        $this->notificacionId = $notificacionId;
    }

    /**
     * Execute the job.
     */
    public function handle(PushNotificationService $pushService): void
    {
        try {
            Log::info("🚀 Procesando job de push notification", [
                'token_preview' => substr($this->token, 0, 20) . '...',
                'titulo' => $this->titulo,
                'notificacion_id' => $this->notificacionId,
                'intento' => $this->attempts(),
            ]);

            $resultado = $pushService->enviar(
                $this->token,
                $this->titulo,
                $this->mensaje,
                $this->data
            );

            if ($resultado['success']) {
                Log::info("✅ Push notification enviada exitosamente desde job", [
                    'message_id' => $resultado['message_id'] ?? null,
                    'notificacion_id' => $this->notificacionId,
                ]);
                
                // Actualizar notificación si existe
                if ($this->notificacionId) {
                    $notificacion = Notificacion::find($this->notificacionId);
                    if ($notificacion) {
                        $notificacion->marcarEnviada();
                    }
                }
            } else {
                Log::error("❌ Push notification falló", [
                    'error' => $resultado['error'] ?? 'Error desconocido',
                    'invalid_token' => $resultado['invalid_token'] ?? false,
                    'notificacion_id' => $this->notificacionId,
                ]);
                
                // Si el token es inválido, desactivarlo
                if ($resultado['invalid_token'] ?? false) {
                    $this->desactivarToken();
                }
                
                // Actualizar notificación si existe
                if ($this->notificacionId) {
                    $notificacion = Notificacion::find($this->notificacionId);
                    if ($notificacion) {
                        $notificacion->marcarFallida($resultado['error'] ?? 'Error desconocido');
                    }
                }
                
                throw new \Exception($resultado['error'] ?? 'Error desconocido');
            }
            
        } catch (\Exception $e) {
            Log::error("❌ Excepción en job de push notification", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'notificacion_id' => $this->notificacionId,
                'intento' => $this->attempts(),
                'max_intentos' => $this->tries,
            ]);
            
            if ($this->attempts() >= $this->tries) {
                Log::warning("⚠️ Push notification falló después de {$this->tries} intentos", [
                    'token_preview' => substr($this->token, 0, 20) . '...',
                    'notificacion_id' => $this->notificacionId,
                ]);
            }
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de push notification falló definitivamente: " . $exception->getMessage());
    }

    /**
     * Desactivar token inválido
     */
    protected function desactivarToken(): void
    {
        Dispositivo::where('token_push', $this->token)->update(['activo' => false]);
        Log::info("Token de dispositivo desactivado: " . substr($this->token, 0, 20) . '...');
    }
}

