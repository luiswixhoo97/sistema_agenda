<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Cliente;
use App\Models\Cita;
use App\Models\PlantillaNotificacion;
use App\Jobs\EnviarEmailJob;
use App\Jobs\EnviarWhatsAppJob;
use App\Jobs\EnviarPushNotificationJob;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    /**
     * Enviar notificación de confirmación de cita
     */
    public function notificarCitaAgendada(Cita $cita): void
    {
        $cliente = $cita->cliente;
        
        $datos = $this->preparaDatosCita($cita);
        
        // Registrar notificación en BD
        $notificacion = Notificacion::create([
            'cita_id' => $cita->id,
            'tipo' => 'confirmacion',
            'canal' => 'multiple',
            'estado' => 'pendiente',
            'mensaje' => $this->renderizarPlantilla('confirmacion_cita', $datos),
        ]);

        // Despachar jobs según preferencias del cliente
        $this->despacharNotificaciones($cliente, $notificacion, $datos, 'confirmacion_cita');
    }

    /**
     * Enviar recordatorio de cita
     */
    public function notificarRecordatorio(Cita $cita): void
    {
        $cliente = $cita->cliente;
        
        $datos = $this->preparaDatosCita($cita);
        
        $notificacion = Notificacion::create([
            'cita_id' => $cita->id,
            'tipo' => 'recordatorio',
            'canal' => 'multiple',
            'estado' => 'pendiente',
            'mensaje' => $this->renderizarPlantilla('recordatorio_cita', $datos),
        ]);

        $this->despacharNotificaciones($cliente, $notificacion, $datos, 'recordatorio_cita');
    }

    /**
     * Notificar cancelación de cita
     */
    public function notificarCitaCancelada(Cita $cita, string $motivo = ''): void
    {
        $cliente = $cita->cliente;
        
        $datos = $this->preparaDatosCita($cita);
        $datos['motivo_cancelacion'] = $motivo;
        
        $notificacion = Notificacion::create([
            'cita_id' => $cita->id,
            'tipo' => 'cancelacion',
            'canal' => 'multiple',
            'estado' => 'pendiente',
            'mensaje' => $this->renderizarPlantilla('cancelacion_cita', $datos),
        ]);

        $this->despacharNotificaciones($cliente, $notificacion, $datos, 'cancelacion_cita');
    }

    /**
     * Notificar modificación de cita
     */
    public function notificarCitaModificada(Cita $cita, array $cambios = []): void
    {
        $cliente = $cita->cliente;
        
        $datos = $this->preparaDatosCita($cita);
        $datos['cambios'] = $cambios;
        
        $notificacion = Notificacion::create([
            'cita_id' => $cita->id,
            'tipo' => 'modificacion',
            'canal' => 'multiple',
            'estado' => 'pendiente',
            'mensaje' => $this->renderizarPlantilla('modificacion_cita', $datos),
        ]);

        $this->despacharNotificaciones($cliente, $notificacion, $datos, 'modificacion_cita');
    }

    /**
     * Enviar OTP al cliente
     */
    public function enviarOTP(Cliente $cliente, string $codigo): void
    {
        $datos = [
            'cliente_nombre' => $cliente->nombre,
            'codigo_otp' => $codigo,
            'expiracion_minutos' => 5,
        ];

        // OTP solo por WhatsApp o SMS
        if ($cliente->telefono) {
            EnviarWhatsAppJob::dispatch(
                $cliente->telefono,
                $this->renderizarPlantilla('otp', $datos),
                'otp'
            )->onQueue('high');
        }
    }

    /**
     * Preparar datos de la cita para las plantillas
     */
    protected function preparaDatosCita(Cita $cita): array
    {
        $cita->load(['cliente', 'empleado', 'servicios']);
        
        $serviciosNombres = $cita->servicios->pluck('nombre')->implode(', ');
        $precioTotal = $cita->servicios->sum('pivot.precio_aplicado');
        
        return [
            'cliente_nombre' => $cita->cliente->nombre,
            'empleado_nombre' => $cita->empleado->nombre ?? 'No asignado',
            'fecha' => $cita->fecha_hora->format('d/m/Y'),
            'hora' => $cita->fecha_hora->format('H:i'),
            'servicios' => $serviciosNombres,
            'precio_total' => number_format($precioTotal, 2),
            'duracion_total' => $cita->duracion_total,
            'notas' => $cita->notas ?? '',
            'negocio_nombre' => config('app.name'),
            'negocio_telefono' => config('negocio.telefono', ''),
            'negocio_direccion' => config('negocio.direccion', ''),
        ];
    }

    /**
     * Despachar notificaciones según canales disponibles
     */
    protected function despacharNotificaciones(
        Cliente $cliente, 
        Notificacion $notificacion, 
        array $datos, 
        string $tipoPlantilla
    ): void {
        // Email
        if ($cliente->email) {
            EnviarEmailJob::dispatch(
                $cliente->email,
                $this->obtenerAsuntoEmail($tipoPlantilla),
                $tipoPlantilla,
                $datos,
                $notificacion->id
            )->onQueue('notifications');
        }

        // WhatsApp
        if ($cliente->telefono) {
            EnviarWhatsAppJob::dispatch(
                $cliente->telefono,
                $this->renderizarPlantilla($tipoPlantilla, $datos),
                $tipoPlantilla,
                $notificacion->id
            )->onQueue('notifications');
        }

        // Push Notification
        $dispositivos = $cliente->dispositivos()->where('activo', true)->get();
        foreach ($dispositivos as $dispositivo) {
            EnviarPushNotificationJob::dispatch(
                $dispositivo->token,
                $this->obtenerTituloPush($tipoPlantilla),
                $this->renderizarPlantilla($tipoPlantilla . '_corto', $datos),
                $this->obtenerDataPush($notificacion),
                $notificacion->id
            )->onQueue('notifications');
        }
    }

    /**
     * Renderizar plantilla de mensaje
     */
    protected function renderizarPlantilla(string $tipo, array $datos): string
    {
        $plantilla = PlantillaNotificacion::where('tipo', $tipo)
            ->where('activa', true)
            ->first();

        if (!$plantilla) {
            return $this->obtenerPlantillaPorDefecto($tipo, $datos);
        }

        $contenido = $plantilla->contenido;
        
        foreach ($datos as $key => $valor) {
            if (is_string($valor) || is_numeric($valor)) {
                $contenido = str_replace('{{' . $key . '}}', $valor, $contenido);
            }
        }

        return $contenido;
    }

    /**
     * Obtener plantilla por defecto si no existe en BD
     */
    protected function obtenerPlantillaPorDefecto(string $tipo, array $datos): string
    {
        $plantillas = [
            'confirmacion_cita' => "¡Hola {{cliente_nombre}}! Tu cita ha sido confirmada para el {{fecha}} a las {{hora}}. Servicios: {{servicios}}. Te esperamos en {{negocio_nombre}}.",
            
            'recordatorio_cita' => "¡Hola {{cliente_nombre}}! Te recordamos tu cita mañana {{fecha}} a las {{hora}}. Servicios: {{servicios}}. ¡Te esperamos!",
            
            'cancelacion_cita' => "Hola {{cliente_nombre}}, tu cita del {{fecha}} a las {{hora}} ha sido cancelada. {{motivo_cancelacion}} Puedes agendar una nueva cita en cualquier momento.",
            
            'modificacion_cita' => "Hola {{cliente_nombre}}, tu cita ha sido modificada. Nueva fecha: {{fecha}} a las {{hora}}. Servicios: {{servicios}}.",
            
            'otp' => "Tu código de verificación es: {{codigo_otp}}. Válido por {{expiracion_minutos}} minutos. No compartas este código con nadie.",
            
            'confirmacion_cita_corto' => "Cita confirmada: {{fecha}} {{hora}}",
            'recordatorio_cita_corto' => "Recordatorio: Cita mañana {{hora}}",
            'cancelacion_cita_corto' => "Tu cita del {{fecha}} fue cancelada",
            'modificacion_cita_corto' => "Cita modificada: {{fecha}} {{hora}}",
        ];

        $plantilla = $plantillas[$tipo] ?? "Notificación de {{negocio_nombre}}";
        
        foreach ($datos as $key => $valor) {
            if (is_string($valor) || is_numeric($valor)) {
                $plantilla = str_replace('{{' . $key . '}}', $valor, $plantilla);
            }
        }

        return $plantilla;
    }

    /**
     * Obtener asunto de email según tipo
     */
    protected function obtenerAsuntoEmail(string $tipo): string
    {
        $asuntos = [
            'confirmacion_cita' => 'Confirmación de tu cita',
            'recordatorio_cita' => 'Recordatorio de cita',
            'cancelacion_cita' => 'Cancelación de cita',
            'modificacion_cita' => 'Modificación de cita',
        ];

        return $asuntos[$tipo] ?? 'Notificación';
    }

    /**
     * Obtener título para push notification
     */
    protected function obtenerTituloPush(string $tipo): string
    {
        $titulos = [
            'confirmacion_cita' => '✅ Cita Confirmada',
            'recordatorio_cita' => '⏰ Recordatorio',
            'cancelacion_cita' => '❌ Cita Cancelada',
            'modificacion_cita' => '📝 Cita Modificada',
        ];

        return $titulos[$tipo] ?? 'Notificación';
    }

    /**
     * Obtener data adicional para push notification
     */
    protected function obtenerDataPush(Notificacion $notificacion): array
    {
        return [
            'notificacion_id' => $notificacion->id,
            'cita_id' => $notificacion->cita_id,
            'tipo' => $notificacion->tipo,
            'click_action' => 'OPEN_CITA',
        ];
    }

    /**
     * Programar recordatorios automáticos
     */
    public function programarRecordatorios(): void
    {
        // Obtener citas de mañana que necesitan recordatorio
        $manana = now()->addDay()->startOfDay();
        $finManana = now()->addDay()->endOfDay();

        $citas = Cita::whereBetween('fecha_hora', [$manana, $finManana])
            ->where('estado', 'confirmada')
            ->whereDoesntHave('notificaciones', function ($query) {
                $query->where('tipo', 'recordatorio')
                    ->where('created_at', '>=', now()->startOfDay());
            })
            ->get();

        foreach ($citas as $cita) {
            $this->notificarRecordatorio($cita);
        }

        Log::info("Recordatorios enviados: " . $citas->count());
    }
}

