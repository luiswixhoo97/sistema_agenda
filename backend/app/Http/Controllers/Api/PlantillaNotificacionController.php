<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlantillaNotificacion;
use App\Models\Cliente;
use App\Services\WhatsAppService;
use App\Jobs\EnviarWhatsAppJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PlantillaNotificacionController extends Controller
{
    protected WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Obtener todas las plantillas
     */
    public function index(): JsonResponse
    {
        $plantillas = PlantillaNotificacion::orderBy('tipo')->get();

        // Agrupar por tipo
        $agrupadas = $plantillas->groupBy('tipo')->map(function ($items, $tipo) {
            return [
                'tipo' => $tipo,
                'nombre' => $this->getNombreTipo($tipo),
                'descripcion' => $this->getDescripcionTipo($tipo),
                'variables' => $this->getVariablesTipo($tipo),
                'plantillas' => $items->map(fn($p) => [
                    'id' => $p->id,
                    'medio' => $p->medio,
                    'asunto' => $p->asunto,
                    'contenido' => $p->contenido,
                    'activo' => $p->activo,
                ]),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'plantillas' => $agrupadas,
        ]);
    }

    /**
     * Obtener una plantilla específica
     */
    public function show(int $id): JsonResponse
    {
        $plantilla = PlantillaNotificacion::find($id);

        if (!$plantilla) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'plantilla' => [
                'id' => $plantilla->id,
                'tipo' => $plantilla->tipo,
                'medio' => $plantilla->medio,
                'asunto' => $plantilla->asunto,
                'contenido' => $plantilla->contenido,
                'variables' => $this->getVariablesTipo($plantilla->tipo),
                'activo' => $plantilla->activo,
            ],
        ]);
    }

    /**
     * Actualizar una plantilla
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $plantilla = PlantillaNotificacion::find($id);

        if (!$plantilla) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada',
            ], 404);
        }

        $request->validate([
            'contenido' => 'required|string|min:10',
            'asunto' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $plantilla->update([
            'contenido' => $request->contenido,
            'asunto' => $request->asunto,
            'activo' => $request->activo ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla actualizada correctamente',
            'plantilla' => $plantilla,
        ]);
    }

    /**
     * Restablecer plantilla a valores por defecto
     */
    public function restablecer(int $id): JsonResponse
    {
        $plantilla = PlantillaNotificacion::find($id);

        if (!$plantilla) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada',
            ], 404);
        }

        $contenidoPorDefecto = $this->getContenidoPorDefecto($plantilla->tipo);

        if ($contenidoPorDefecto) {
            $plantilla->update([
                'contenido' => $contenidoPorDefecto,
                'activo' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Plantilla restablecida a valores por defecto',
            'plantilla' => $plantilla,
        ]);
    }

    /**
     * Vista previa de una plantilla con datos de ejemplo
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $plantilla = PlantillaNotificacion::find($id);

        if (!$plantilla) {
            return response()->json([
                'success' => false,
                'message' => 'Plantilla no encontrada',
            ], 404);
        }

        $datosEjemplo = $this->getDatosEjemplo($plantilla->tipo);
        $contenidoRenderizado = $plantilla->renderizar($datosEjemplo);

        return response()->json([
            'success' => true,
            'preview' => $contenidoRenderizado,
            'datos_ejemplo' => $datosEjemplo,
        ]);
    }

    /**
     * Enviar comunicación masiva
     */
    public function enviarComunicacion(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => 'required|in:individual,seleccion,todos',
            'telefonos' => 'required_if:tipo,individual,seleccion|array',
            'telefonos.*' => 'string|min:10',
            'mensaje' => 'required|string|min:10|max:2000',
            'titulo' => 'nullable|string|max:100',
        ]);

        $telefonos = [];

        switch ($request->tipo) {
            case 'individual':
            case 'seleccion':
                $telefonos = $request->telefonos;
                break;
            case 'todos':
                $telefonos = Cliente::whereNotNull('telefono')
                    ->where('notificaciones_whatsapp', true)
                    ->pluck('telefono')
                    ->toArray();
                break;
        }

        if (empty($telefonos)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay destinatarios para enviar el mensaje',
            ], 422);
        }

        $titulo = $request->titulo ? "📢 *{$request->titulo}*\n\n" : '';
        $mensajeCompleto = $titulo . $request->mensaje;

        $enviados = 0;
        $fallidos = 0;
        $errores = [];

        foreach ($telefonos as $telefono) {
            try {
                // Usar job para no bloquear
                EnviarWhatsAppJob::dispatch(
                    $telefono,
                    $mensajeCompleto,
                    'comunicacion'
                )->onQueue('notifications');
                
                $enviados++;
            } catch (\Exception $e) {
                $fallidos++;
                $errores[] = [
                    'telefono' => $telefono,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Registrar en log
        Log::info("📢 Comunicación masiva enviada", [
            'tipo' => $request->tipo,
            'total_destinatarios' => count($telefonos),
            'enviados' => $enviados,
            'fallidos' => $fallidos,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Comunicación enviada a {$enviados} destinatarios",
            'estadisticas' => [
                'total' => count($telefonos),
                'enviados' => $enviados,
                'fallidos' => $fallidos,
            ],
        ]);
    }

    /**
     * Obtener lista de clientes para selección
     */
    public function getClientes(Request $request): JsonResponse
    {
        $query = Cliente::whereNotNull('telefono')
            ->where('active', true);

        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('telefono', 'like', "%{$buscar}%");
            });
        }

        $clientes = $query->orderBy('nombre')
            ->limit(100)
            ->get(['id', 'nombre', 'telefono', 'notificaciones_whatsapp']);

        return response()->json([
            'success' => true,
            'clientes' => $clientes,
        ]);
    }

    /**
     * Obtener estadísticas de mensajes
     */
    public function getEstadisticas(): JsonResponse
    {
        $hoy = now()->startOfDay();
        $semana = now()->subWeek();
        $mes = now()->subMonth();

        // Contar notificaciones enviadas
        $stats = [
            'hoy' => DB::table('notificaciones')
                ->where('created_at', '>=', $hoy)
                ->where('estado', 'enviada')
                ->count(),
            'semana' => DB::table('notificaciones')
                ->where('created_at', '>=', $semana)
                ->where('estado', 'enviada')
                ->count(),
            'mes' => DB::table('notificaciones')
                ->where('created_at', '>=', $mes)
                ->where('estado', 'enviada')
                ->count(),
            'clientes_whatsapp' => Cliente::where('notificaciones_whatsapp', true)->count(),
            'clientes_total' => Cliente::count(),
        ];

        return response()->json([
            'success' => true,
            'estadisticas' => $stats,
        ]);
    }

    // ==================== HELPERS ====================

    private function getNombreTipo(string $tipo): string
    {
        return match ($tipo) {
            'otp' => 'Código de Verificación (OTP)',
            'confirmacion_cita' => 'Confirmación de Cita',
            'modificacion_cita' => 'Reagendamiento de Cita',
            'cancelacion_cita' => 'Cancelación de Cita',
            'recordatorio_cita' => 'Recordatorio de Cita',
            default => ucfirst(str_replace('_', ' ', $tipo)),
        };
    }

    private function getDescripcionTipo(string $tipo): string
    {
        return match ($tipo) {
            'otp' => 'Mensaje enviado cuando el cliente solicita verificar su teléfono',
            'confirmacion_cita' => 'Mensaje enviado al confirmar una nueva cita (incluye QR)',
            'modificacion_cita' => 'Mensaje enviado cuando se reagenda una cita (incluye nuevo QR)',
            'cancelacion_cita' => 'Mensaje enviado cuando se cancela una cita',
            'recordatorio_cita' => 'Mensaje de recordatorio enviado antes de la cita',
            default => 'Plantilla de notificación',
        };
    }

    private function getVariablesTipo(string $tipo): array
    {
        $variablesComunes = [
            'cliente_nombre' => 'Nombre del cliente',
            'negocio_nombre' => 'Nombre del negocio',
        ];

        $variablesCita = array_merge($variablesComunes, [
            'fecha' => 'Fecha de la cita (dd/mm/yyyy)',
            'hora' => 'Hora de la cita (HH:mm)',
            'servicios' => 'Lista de servicios',
            'empleado_nombre' => 'Nombre del empleado',
            'precio_total' => 'Precio total',
            'duracion_total' => 'Duración en minutos',
            'negocio_direccion' => 'Dirección del negocio',
        ]);

        return match ($tipo) {
            'otp' => [
                'codigo_otp' => 'Código de verificación (6 dígitos)',
                'expiracion_minutos' => 'Minutos de validez del código',
            ],
            'confirmacion_cita' => $variablesCita,
            'modificacion_cita' => $variablesCita,
            'cancelacion_cita' => array_merge($variablesCita, [
                'motivo_cancelacion' => 'Motivo de la cancelación',
            ]),
            'recordatorio_cita' => $variablesCita,
            default => $variablesComunes,
        };
    }

    private function getDatosEjemplo(string $tipo): array
    {
        $datosComunes = [
            'cliente_nombre' => 'María García',
            'negocio_nombre' => config('app.name', 'Mi Negocio'),
            'negocio_direccion' => 'Av. Principal #123, Col. Centro',
            'negocio_telefono' => '33 1234 5678',
        ];

        $datosCita = array_merge($datosComunes, [
            'fecha' => now()->addDays(3)->format('d/m/Y'),
            'hora' => '14:30',
            'servicios' => 'Corte de cabello, Tinte',
            'empleado_nombre' => 'Ana López',
            'precio_total' => '450.00',
            'duracion_total' => '90',
        ]);

        return match ($tipo) {
            'otp' => [
                'codigo_otp' => '123456',
                'expiracion_minutos' => '3',
            ],
            'confirmacion_cita' => $datosCita,
            'modificacion_cita' => $datosCita,
            'cancelacion_cita' => array_merge($datosCita, [
                'motivo_cancelacion' => '📋 Motivo: Solicitud del cliente',
            ]),
            'recordatorio_cita' => $datosCita,
            default => $datosComunes,
        };
    }

    private function getContenidoPorDefecto(string $tipo): ?string
    {
        return match ($tipo) {
            'otp' => "🔐 *Código de verificación*\n\nTu código es: *{{codigo_otp}}*\n\n⏱️ Válido por {{expiracion_minutos}} minutos.\n\n⚠️ No compartas este código con nadie.",
            'confirmacion_cita' => "¡Hola {{cliente_nombre}}! 👋\n\n✅ Tu cita ha sido confirmada:\n\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicios: {{servicios}}\n👤 Empleado: {{empleado_nombre}}\n💰 Precio: \${{precio_total}}\n⏱️ Duración: {{duracion_total}} minutos\n\n📍 {{negocio_nombre}}\n{{negocio_direccion}}\n\n¡Te esperamos! 😊",
            'modificacion_cita' => "¡Hola {{cliente_nombre}}! 👋\n\n📝 Tu cita ha sido reagendada:\n\n📅 Nueva fecha: {{fecha}}\n⏰ Nueva hora: {{hora}}\n💇 Servicios: {{servicios}}\n👤 Empleado: {{empleado_nombre}}\n💰 Precio: \${{precio_total}}\n⏱️ Duración: {{duracion_total}} minutos\n\n📍 {{negocio_nombre}}\n{{negocio_direccion}}\n\n¡Te esperamos! 😊",
            'cancelacion_cita' => "Hola {{cliente_nombre}} 👋\n\n❌ Tu cita ha sido cancelada:\n\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicios: {{servicios}}\n\n{{motivo_cancelacion}}\n\nPuedes agendar una nueva cita en cualquier momento. ¡Esperamos verte pronto! 😊",
            'recordatorio_cita' => "¡Hola {{cliente_nombre}}! 👋\n\n⏰ Te recordamos tu cita para mañana:\n\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicios: {{servicios}}\n👤 Empleado: {{empleado_nombre}}\n\n📍 {{negocio_nombre}}\n{{negocio_direccion}}\n\n¡Te esperamos! 😊",
            default => null,
        };
    }
}

