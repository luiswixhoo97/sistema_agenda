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
            $resultado = $pushService->enviar(
                $this->token,
                $this->titulo,
                $this->mensaje,
                $this->data
            );

            if ($resultado['success']) {
                Log::info("Push notification enviada exitosamente");
            } else {
                // Si el token es inválido, desactivarlo
                if ($resultado['invalid_token'] ?? false) {
                    $this->desactivarToken();
                }
                throw new \Exception($resultado['error'] ?? 'Error desconocido');
            }
            
        } catch (\Exception $e) {
            Log::error("Error enviando push notification: " . $e->getMessage());
            
            if ($this->attempts() >= $this->tries) {
                // No marcamos como fallido ya que puede haber otros canales exitosos
                Log::warning("Push notification falló después de {$this->tries} intentos");
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

