<?php

namespace App\Jobs;

use App\Models\Notificacion;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 30;

    protected string $telefono;
    protected string $mensaje;
    protected string $tipoPlantilla;
    protected ?int $notificacionId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $telefono, 
        string $mensaje, 
        string $tipoPlantilla,
        ?int $notificacionId = null
    ) {
        $this->telefono = $telefono;
        $this->mensaje = $mensaje;
        $this->tipoPlantilla = $tipoPlantilla;
        $this->notificacionId = $notificacionId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsAppService): void
    {
        try {
            $resultado = $whatsAppService->enviarMensaje(
                $this->telefono,
                $this->mensaje,
                $this->tipoPlantilla
            );

            if ($resultado['success']) {
                $this->actualizarEstadoNotificacion('enviado');
                Log::info("WhatsApp enviado exitosamente a: {$this->telefono}");
            } else {
                throw new \Exception($resultado['error'] ?? 'Error desconocido');
            }
            
        } catch (\Exception $e) {
            Log::error("Error enviando WhatsApp a {$this->telefono}: " . $e->getMessage());
            
            if ($this->attempts() >= $this->tries) {
                $this->actualizarEstadoNotificacion('fallido');
            }
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de WhatsApp falló definitivamente para {$this->telefono}: " . $exception->getMessage());
        $this->actualizarEstadoNotificacion('fallido');
    }

    /**
     * Actualizar estado de la notificación en BD
     */
    protected function actualizarEstadoNotificacion(string $estado): void
    {
        if ($this->notificacionId) {
            Notificacion::where('id', $this->notificacionId)
                ->update([
                    'estado' => $estado,
                    'enviado_at' => $estado === 'enviado' ? now() : null,
                ]);
        }
    }
}

