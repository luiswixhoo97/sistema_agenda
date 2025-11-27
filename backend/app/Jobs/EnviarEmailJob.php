<?php

namespace App\Jobs;

use App\Models\Notificacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EnviarEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 30;

    protected string $email;
    protected string $asunto;
    protected string $plantilla;
    protected array $datos;
    protected ?int $notificacionId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $email, 
        string $asunto, 
        string $plantilla, 
        array $datos,
        ?int $notificacionId = null
    ) {
        $this->email = $email;
        $this->asunto = $asunto;
        $this->plantilla = $plantilla;
        $this->datos = $datos;
        $this->notificacionId = $notificacionId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::send('emails.' . $this->plantilla, $this->datos, function ($message) {
                $message->to($this->email)
                    ->subject($this->asunto)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            $this->actualizarEstadoNotificacion('enviado');
            
            Log::info("Email enviado exitosamente a: {$this->email}");
            
        } catch (\Exception $e) {
            Log::error("Error enviando email a {$this->email}: " . $e->getMessage());
            
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
        Log::error("Job de email falló definitivamente para {$this->email}: " . $exception->getMessage());
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

