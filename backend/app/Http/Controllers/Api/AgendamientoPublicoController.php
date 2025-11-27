<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cita;
use App\Models\CitaServicio;
use App\Models\OtpCode;
use App\Models\Servicio;
use App\Services\DisponibilidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgendamientoPublicoController extends Controller
{
    protected DisponibilidadService $disponibilidadService;

    public function __construct(DisponibilidadService $disponibilidadService)
    {
        $this->disponibilidadService = $disponibilidadService;
    }

    /**
     * Enviar OTP para confirmar agendamiento
     */
    public function enviarOtp(Request $request): JsonResponse
    {
        $request->validate([
            'telefono' => 'required|string|size:10',
        ]);

        $telefono = $request->telefono;

        // Generar código OTP
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiracion = now()->addMinutes(10);

        // Eliminar OTPs anteriores para este teléfono y tipo
        OtpCode::where('telefono', $telefono)
            ->where('tipo', 'agendamiento')
            ->delete();

        // Crear nuevo OTP
        OtpCode::create([
            'telefono' => $telefono,
            'tipo' => 'agendamiento',
            'codigo' => $codigo,
            'intentos' => 0,
            'expira_at' => $expiracion,
        ]);

        // TODO: Enviar OTP por WhatsApp en producción
        // WhatsAppService::enviarOtp($telefono, $codigo);

        $response = [
            'success' => true,
            'message' => 'Código enviado a tu WhatsApp',
        ];

        // En desarrollo, devolver el código
        if (config('app.debug')) {
            $response['codigo_debug'] = $codigo;
            Log::info("🔐 OTP Agendamiento para {$telefono}: {$codigo}");
        }

        return response()->json($response);
    }

    /**
     * Agendar cita pública (crea cliente + cita en una transacción)
     */
    public function agendar(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_nombre' => 'required|string|min:3|max:100',
            'cliente_telefono' => 'required|string|size:10',
            'cliente_email' => 'nullable|email|max:100',
            'codigo_otp' => 'required|string|size:6',
            'empleado_id' => 'required|integer|exists:empleados,id',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
            'fecha_hora' => 'required|date|after_or_equal:now',
            'notas' => 'nullable|string|max:500',
        ]);

        // Verificar OTP
        $otp = OtpCode::where('telefono', $request->cliente_telefono)
            ->where('tipo', 'agendamiento')
            ->where('expira_at', '>', now())
            ->first();

        if (config('app.debug')) {
            $otpDebug = OtpCode::where('telefono', $request->cliente_telefono)->first();
            Log::info('🔍 Buscando OTP:', [
                'telefono' => $request->cliente_telefono,
                'codigo_enviado' => $request->codigo_otp,
                'otp_encontrado' => $otp ? 'Sí' : 'No',
                'otp_en_db' => $otpDebug ? [
                    'codigo' => $otpDebug->codigo,
                    'tipo' => $otpDebug->tipo,
                    'expira_at' => $otpDebug->expira_at,
                    'ahora' => now()->toDateTimeString(),
                ] : 'No existe',
            ]);
        }

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Código expirado. Solicita uno nuevo.',
            ], 422);
        }

        if ($otp->intentos >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos. Solicita un nuevo código.',
            ], 422);
        }

        if ($otp->codigo !== $request->codigo_otp) {
            $otp->increment('intentos');
            return response()->json([
                'success' => false,
                'message' => 'Código incorrecto. Te quedan ' . (3 - $otp->intentos) . ' intentos.',
            ], 422);
        }

        // Verificar disponibilidad
        $disponibilidad = $this->disponibilidadService->verificarDisponibilidad(
            $request->empleado_id,
            $request->fecha_hora,
            $request->servicios
        );

        if (!$disponibilidad['disponible']) {
            return response()->json([
                'success' => false,
                'message' => $disponibilidad['mensaje'] ?? 'Horario no disponible',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Buscar o crear cliente
            $cliente = Cliente::firstOrCreate(
                ['telefono' => $request->cliente_telefono],
                [
                    'nombre' => $request->cliente_nombre,
                    'email' => $request->cliente_email,
                    'active' => true,
                ]
            );

            // Actualizar nombre si el cliente ya existía pero puso otro nombre
            if ($cliente->wasRecentlyCreated === false && $cliente->nombre !== $request->cliente_nombre) {
                // Mantener el nombre existente
            }

            // Calcular duración y precio
            $servicios = Servicio::whereIn('id', $request->servicios)->get();
            $duracionTotal = $servicios->sum('duracion');
            $precioTotal = $servicios->sum('precio');

            // Crear cita
            $cita = Cita::create([
                'cliente_id' => $cliente->id,
                'empleado_id' => $request->empleado_id,
                'fecha_hora' => $request->fecha_hora,
                'duracion_total' => $duracionTotal,
                'estado' => 'pendiente',
                'precio_total' => $precioTotal,
                'precio_final' => $precioTotal,
                'notas' => $request->notas,
            ]);

            // Agregar servicios a la cita
            $orden = 1;
            foreach ($servicios as $servicio) {
                CitaServicio::create([
                    'cita_id' => $cita->id,
                    'servicio_id' => $servicio->id,
                    'precio_aplicado' => $servicio->precio,
                    'orden' => $orden++,
                ]);
            }

            // Eliminar OTP usado
            $otp->delete();

            // Cargar relaciones (servicios son CitaServicio, necesitamos cargar el servicio relacionado)
            $cita->load(['cliente', 'empleado.user', 'servicios.servicio']);

            DB::commit();

            // TODO: Enviar confirmación por WhatsApp
            // NotificacionService::enviarConfirmacionCita($cita);

            Log::info("✅ Cita agendada públicamente - ID: {$cita->id}, Cliente: {$cliente->nombre}");

            return response()->json([
                'success' => true,
                'message' => '¡Cita agendada exitosamente!',
                'cita' => [
                    'id' => $cita->id,
                    'fecha' => $cita->fecha_hora->format('Y-m-d'),
                    'hora' => $cita->fecha_hora->format('H:i'),
                    'fecha_texto' => $cita->fecha_hora->format('d/m/Y'),
                    'hora_texto' => $cita->fecha_hora->format('h:i A'),
                    'duracion_total' => $cita->duracion_total,
                    'estado' => $cita->estado,
                    'precio_final' => $cita->precio_final,
                    'cliente' => [
                        'id' => $cliente->id,
                        'nombre' => $cliente->nombre,
                        'telefono' => $cliente->telefono,
                    ],
                    'empleado' => [
                        'id' => $cita->empleado->id,
                        'nombre' => $cita->empleado->user->nombre ?? 'Empleado',
                    ],
                    'servicios' => $cita->servicios->map(fn($citaServicio) => [
                        'id' => $citaServicio->servicio->id,
                        'nombre' => $citaServicio->servicio->nombre,
                        'precio_aplicado' => $citaServicio->precio_aplicado,
                        'duracion' => $citaServicio->servicio->duracion,
                    ]),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al agendar cita pública: ' . $e->getMessage());
            
            if (config('app.debug')) {
                Log::error('Detalle:', [
                    'trace' => $e->getTraceAsString(),
                    'request' => $request->all(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al agendar la cita. Intenta de nuevo.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

