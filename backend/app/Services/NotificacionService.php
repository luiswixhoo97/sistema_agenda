<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\Cliente;
use App\Models\Cita;
use App\Models\PlantillaNotificacion;
use App\Models\Configuracion;
use App\Jobs\EnviarEmailJob;
use App\Jobs\EnviarWhatsAppJob;
use App\Jobs\EnviarPushNotificationJob;
use App\Services\QrService;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    public function __construct(
        private QrService $qrService
    ) {}

    /**
     * Enviar notificación de confirmación de cita
     */
    public function notificarCitaAgendada(Cita $cita): void
    {
        $cliente = $cita->cliente;
        
        Log::info("📧 Iniciando notificación de cita agendada", [
            'cita_id' => $cita->id,
            'cliente_id' => $cliente->id,
            'cliente_telefono' => $cliente->telefono,
            'token_qr' => $cita->token_qr,
        ]);
        
        $datos = $this->preparaDatosCita($cita);
        
        // Generar QR code para la cita
        $qrUrl = $this->qrService->generarQrCita($cita);
        
        Log::info("📱 QR generado", [
            'cita_id' => $cita->id,
            'qr_url' => $qrUrl,
        ]);
        
        // Registrar notificación en BD
        $notificacion = Notificacion::create([
            'cita_id' => $cita->id,
            'cliente_id' => $cliente->id,
            'tipo' => 'confirmacion',
            'medio' => 'whatsapp',
            'estado' => 'pendiente',
            'mensaje' => $this->renderizarPlantilla('confirmacion_cita', $datos),
        ]);

        // Despachar jobs según preferencias del cliente (con QR si está disponible)
        $this->despacharNotificaciones($cliente, $notificacion, $datos, 'confirmacion_cita', $qrUrl);
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
        
        Log::info("📧 Iniciando notificación de cita cancelada", [
            'cita_id' => $cita->id,
            'cliente_id' => $cliente->id,
            'cliente_telefono' => $cliente->telefono,
            'motivo' => $motivo,
        ]);
        
        $datos = $this->preparaDatosCita($cita);
        $datos['motivo_cancelacion'] = $motivo ? "📋 Motivo: {$motivo}" : '';
        
        $notificacion = Notificacion::create([
            'cita_id' => $cita->id,
            'cliente_id' => $cliente->id,
            'tipo' => 'cancelacion',
            'medio' => 'whatsapp',
            'estado' => 'pendiente',
            'mensaje' => $this->renderizarPlantilla('cancelacion_cita', $datos),
        ]);

        // Cancelación no lleva QR
        $this->despacharNotificaciones($cliente, $notificacion, $datos, 'cancelacion_cita');
    }

    /**
     * Notificar modificación/reagendamiento de cita
     * Incluye nuevo QR code con los datos actualizados
     */
    public function notificarCitaModificada(Cita $cita, array $cambios = []): void
    {
        $cliente = $cita->cliente;
        
        Log::info("📧 Iniciando notificación de cita modificada/reagendada", [
            'cita_id' => $cita->id,
            'cliente_id' => $cliente->id,
            'cliente_telefono' => $cliente->telefono,
            'cambios' => $cambios,
        ]);
        
        $datos = $this->preparaDatosCita($cita);
        $datos['cambios'] = $cambios;
        
        // Regenerar QR code para la cita modificada
        // El QR contiene la nueva información de la cita
        $qrUrl = $this->qrService->generarQrCita($cita);
        
        Log::info("📱 Nuevo QR generado para cita modificada", [
            'cita_id' => $cita->id,
            'qr_generado' => !empty($qrUrl),
        ]);
        
        $notificacion = Notificacion::create([
            'cita_id' => $cita->id,
            'cliente_id' => $cliente->id,
            'tipo' => 'modificacion',
            'medio' => 'whatsapp',
            'estado' => 'pendiente',
            'mensaje' => $this->renderizarPlantilla('modificacion_cita', $datos),
        ]);

        // Despachar con el nuevo QR
        $this->despacharNotificaciones($cliente, $notificacion, $datos, 'modificacion_cita', $qrUrl);
    }

    /**
     * Enviar OTP al cliente
     */
    public function enviarOTP(Cliente $cliente, string $codigo): void
    {
        $minutosExpiracion = \App\Models\OtpCode::MINUTOS_EXPIRACION; // 3 minutos
        
        $datos = [
            'cliente_nombre' => $cliente->nombre,
            'codigo_otp' => $codigo,
            'expiracion_minutos' => $minutosExpiracion,
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
        $cita->load(['cliente', 'empleado', 'servicios.servicio']);
        
        // Obtener nombres de servicios desde la relación
        if ($cita->servicios->isEmpty()) {
            // Si no hay servicios en la tabla pivot, usar el servicio principal
            $serviciosNombres = $cita->servicio->nombre ?? 'Servicio no especificado';
        } else {
            $serviciosNombres = $cita->servicios->map(function ($citaServicio) {
                return $citaServicio->servicio->nombre ?? 'Servicio no especificado';
            })->implode(', ');
        }
        
        // Usar precio_final de la cita directamente (ya incluye descuentos)
        $precioTotal = $cita->precio_final ?? 0;
        
        // Obtener datos del negocio desde la tabla de configuración
        // Usar las claves que coinciden con el frontend: nombre_negocio, direccion, telefono
        $negocioNombre = Configuracion::get('nombre_negocio', config('app.name', 'Mi Negocio'));
        $negocioDireccion = Configuracion::get('direccion', '');
        $negocioTelefono = Configuracion::get('telefono', '');
        
        return [
            'cliente_nombre' => $cita->cliente->nombre,
            'empleado_nombre' => $cita->empleado->nombre ?? 'No asignado',
            'fecha' => $cita->fecha_hora->format('d/m/Y'),
            'hora' => $cita->fecha_hora->format('H:i'),
            'servicios' => $serviciosNombres,
            'precio_total' => number_format($precioTotal, 2),
            'duracion_total' => $cita->duracion_total,
            'notas' => $cita->notas ?? '',
            'negocio_nombre' => $negocioNombre,
            'negocio_telefono' => $negocioTelefono,
            'negocio_direccion' => $negocioDireccion,
        ];
    }

    /**
     * Despachar notificaciones según canales disponibles
     */
    protected function despacharNotificaciones(
        Cliente $cliente, 
        Notificacion $notificacion, 
        array $datos, 
        string $tipoPlantilla,
        ?string $qrUrl = null
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

        // WhatsApp (con QR si es confirmación y está disponible)
        if ($cliente->telefono) {
            $mensaje = $this->renderizarPlantilla($tipoPlantilla, $datos);
            
            Log::info("📲 Preparando WhatsApp", [
                'telefono' => $cliente->telefono,
                'tipo' => $tipoPlantilla,
                'tiene_qr' => !empty($qrUrl),
                'qr_url' => $qrUrl,
            ]);
            
            // Si hay QR y es confirmación o modificación, enviar TODO en un solo mensaje con imagen
            // Solo enviar QR si es PNG (WasenderAPI no acepta SVG)
            $esPng = $qrUrl && (str_contains($qrUrl, 'image/png') || str_contains($qrUrl, '.png'));
            $tiposConQr = ['confirmacion_cita', 'modificacion_cita'];
            
            if ($qrUrl && in_array($tipoPlantilla, $tiposConQr) && $esPng) {
                // Agregar mensaje sobre el QR al final del mensaje
                $textoQr = $tipoPlantilla === 'modificacion_cita' 
                    ? "\n\n📱 Aquí tienes tu nuevo código QR para la cita reagendada."
                    : "\n\n📱 Escanea el código QR adjunto para marcar tu cita como completada al finalizar el servicio.";
                $mensajeCompleto = $mensaje . $textoQr;
                
                Log::info("📤 Enviando WhatsApp con QR", [
                    'telefono' => $cliente->telefono,
                    'mensaje_length' => strlen($mensajeCompleto),
                    'qr_url' => $qrUrl,
                ]);
                
                // Enviar la imagen con el mensaje completo como caption
                EnviarWhatsAppJob::dispatch(
                    $cliente->telefono,
                    $mensajeCompleto, // El mensaje completo va como caption de la imagen
                    $tipoPlantilla,
                    $notificacion->id,
                    $qrUrl // URL del QR como imagen
                )->onQueue('notifications');
            } else {
                $razon = !$qrUrl 
                    ? 'No hay QR' 
                    : (!in_array($tipoPlantilla, $tiposConQr) 
                        ? 'Tipo de notificación no requiere QR' 
                        : 'QR no es PNG');
                
                Log::info("📤 Enviando WhatsApp sin QR", [
                    'telefono' => $cliente->telefono,
                    'tipo' => $tipoPlantilla,
                    'razon' => $razon,
                ]);
                
                // Si no hay QR o es SVG, enviar mensaje de texto normal
                // Nota: El QR no se puede enviar porque WasenderAPI no acepta SVG
                // y sin imagick no podemos generar PNG
                EnviarWhatsAppJob::dispatch(
                    $cliente->telefono,
                    $mensaje,
                    $tipoPlantilla,
                    $notificacion->id,
                    null // Sin imagen
                )->onQueue('notifications');
            }
        } else {
            Log::warning("⚠️ Cliente sin teléfono para WhatsApp", [
                'cliente_id' => $cliente->id,
            ]);
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
    protected function renderizarPlantilla(string $tipo, array $datos, string $medio = 'whatsapp'): string
    {
        $plantilla = PlantillaNotificacion::where('tipo', $tipo)
            ->where('medio', $medio)
            ->where('activo', true)
            ->first();

        if (!$plantilla) {
            Log::warning("⚠️ Plantilla no encontrada, usando default", [
                'tipo' => $tipo,
                'medio' => $medio,
            ]);
            return $this->obtenerPlantillaPorDefecto($tipo, $datos);
        }

        Log::info("✅ Plantilla encontrada en BD", [
            'tipo' => $tipo,
            'plantilla_id' => $plantilla->id,
            'contenido_preview' => substr($plantilla->contenido, 0, 50),
        ]);

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
            'confirmacion_cita' => "¡Hola {{cliente_nombre}}! 👋\n\n✅ Tu cita ha sido confirmada:\n\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicios: {{servicios}}\n👤 Empleado: {{empleado_nombre}}\n💰 Precio: \${{precio_total}}\n⏱️ Duración: {{duracion_total}} minutos\n\n📍 {{negocio_nombre}}\n{{negocio_direccion}}\n\n¡Te esperamos! 😊",
            
            'recordatorio_cita' => "¡Hola {{cliente_nombre}}! Te recordamos tu cita mañana {{fecha}} a las {{hora}}. Servicios: {{servicios}}. ¡Te esperamos!",
            
            'cancelacion_cita' => "Hola {{cliente_nombre}} 👋\n\n❌ Tu cita ha sido cancelada:\n\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicios: {{servicios}}\n\n{{motivo_cancelacion}}\n\nPuedes agendar una nueva cita en cualquier momento. ¡Esperamos verte pronto! 😊",
            
            'modificacion_cita' => "¡Hola {{cliente_nombre}}! 👋\n\n📝 Tu cita ha sido reagendada:\n\n📅 Nueva fecha: {{fecha}}\n⏰ Nueva hora: {{hora}}\n💇 Servicios: {{servicios}}\n👤 Empleado: {{empleado_nombre}}\n💰 Precio: \${{precio_total}}\n⏱️ Duración: {{duracion_total}} minutos\n\n📍 {{negocio_nombre}}\n{{negocio_direccion}}\n\n¡Te esperamos! 😊",
            
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

