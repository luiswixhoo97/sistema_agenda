<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cita;
use App\Models\CitaServicio;
use App\Models\OtpCode;
use App\Models\Servicio;
use App\Models\PlantillaNotificacion;
use App\Services\DisponibilidadService;
use App\Services\CitaService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AgendamientoPublicoController extends Controller
{
    protected DisponibilidadService $disponibilidadService;
    protected CitaService $citaService;
    protected WhatsAppService $whatsAppService;

    public function __construct(
        DisponibilidadService $disponibilidadService, 
        CitaService $citaService,
        WhatsAppService $whatsAppService
    ) {
        $this->disponibilidadService = $disponibilidadService;
        $this->citaService = $citaService;
        $this->whatsAppService = $whatsAppService;
    }
    
    /**
     * Verificar si hay solapamiento entre dos rangos de horarios
     */
    private function haySolapamientoHorarios(string $inicio1, string $fin1, string $inicio2, string $fin2): bool
    {
        return $inicio1 < $fin2 && $fin1 > $inicio2;
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

        // Verificar rate limiting (máximo 5 OTPs por hora)
        $intentosRecientes = OtpCode::where('telefono', $telefono)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($intentosRecientes >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Has solicitado demasiados códigos. Intenta en 1 hora.',
            ], 429);
        }

        // Generar código OTP
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $minutosExpiracion = OtpCode::MINUTOS_EXPIRACION; // 3 minutos
        $expiracion = now()->addMinutes($minutosExpiracion);

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
            'created_at' => now(),
        ]);

        // Enviar OTP por WhatsApp usando plantilla de BD
        $mensaje = $this->obtenerMensajeOtp($codigo, $minutosExpiracion);

        try {
            $resultado = $this->whatsAppService->enviarMensaje($telefono, $mensaje, 'otp');
            
            if (!$resultado['success']) {
                Log::error("Error enviando OTP por WhatsApp", [
                    'telefono' => $telefono,
                    'error' => $resultado['error'] ?? 'Error desconocido',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Excepción enviando OTP por WhatsApp: " . $e->getMessage(), [
                'telefono' => $telefono,
            ]);
        }

        $response = [
            'success' => true,
            'message' => 'Código enviado a tu WhatsApp',
            'expira_en' => $minutosExpiracion * 60, // segundos
        ];

        // En desarrollo, devolver el código para testing
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
            'promocion_id' => 'nullable|integer|exists:promociones,id',
            'token_reserva' => 'nullable|string',
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

        // Validar token de reserva temporal si se proporciona
        $tokenReserva = $request->input('token_reserva');
        $tokenExcluir = null;
        
        if ($tokenReserva) {
            $reserva = \App\Models\ReservaTemporal::obtenerPorToken($tokenReserva);
            if ($reserva && $reserva->expira_at > now()) {
                // Verificar que el token corresponde al empleado y fecha/hora correctos
                $fechaHoraRequest = \Carbon\Carbon::parse($request->fecha_hora);
                $fechaRequest = $fechaHoraRequest->format('Y-m-d');
                $horaRequest = $fechaHoraRequest->format('H:i');
                $duracionTotal = $this->disponibilidadService->calcularDuracionTotal($request->servicios);
                $horaFinRequest = $fechaHoraRequest->copy()->addMinutes($duracionTotal)->format('H:i');
                
                $fechaReserva = $reserva->fecha instanceof \Carbon\Carbon 
                    ? $reserva->fecha->format('Y-m-d')
                    : \Carbon\Carbon::parse($reserva->fecha)->format('Y-m-d');
                
                if ($reserva->empleado_id == $request->empleado_id && 
                    $fechaReserva === $fechaRequest &&
                    $this->haySolapamientoHorarios($horaRequest, $horaFinRequest, $reserva->hora_inicio, $reserva->hora_fin)) {
                    $tokenExcluir = $tokenReserva;
                    
                    if (config('app.debug')) {
                        Log::info('🔑 Token de reserva válido encontrado para cita simple', [
                            'empleado_id' => $request->empleado_id,
                            'fecha' => $fechaRequest,
                            'hora' => $horaRequest,
                            'token' => $tokenExcluir,
                        ]);
                    }
                }
            }
        }

        // Verificar disponibilidad
        $disponibilidad = $this->disponibilidadService->verificarDisponibilidad(
            $request->empleado_id,
            $request->fecha_hora,
            $request->servicios,
            false, // ignorarAnticipacionMinima
            $tokenExcluir // token de reserva a excluir
        );

        if (!$disponibilidad['disponible']) {
            return response()->json([
                'success' => false,
                'message' => $disponibilidad['mensaje'] ?? 'Horario no disponible',
            ], 422);
        }

        try {
            DB::beginTransaction();
            
            // Liberar reserva temporal si se proporcionó
            if ($tokenReserva && $tokenExcluir) {
                \App\Models\ReservaTemporal::liberar($tokenReserva);
            }

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

            // Usar CitaService para crear la cita (maneja promociones automáticamente)
            $datosCita = [
                'empleado_id' => $request->empleado_id,
                'servicios' => $request->servicios,
                'fecha_hora' => $request->fecha_hora,
                'notas' => $request->notas,
            ];
            
            if ($request->has('promocion_id')) {
                $datosCita['promocion_id'] = $request->promocion_id;
            }

            $resultado = $this->citaService->agendar($datosCita, $cliente->id, true);
            
            if (!$resultado['success']) {
                DB::rollBack();
                return response()->json($resultado, 422);
            }

            $citaFormateada = $resultado['cita'];

            // Eliminar OTP usado
            $otp->delete();

            DB::commit();

            // TODO: Enviar confirmación por WhatsApp
            // NotificacionService::enviarConfirmacionCita($cita);

            Log::info("✅ Cita agendada públicamente - ID: {$citaFormateada['id']}, Cliente: {$cliente->nombre}");

            return response()->json([
                'success' => true,
                'message' => '¡Cita agendada exitosamente!',
                'cita' => $citaFormateada,
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

    /**
     * Agendar múltiples citas coordinadas (diferentes empleados por servicio)
     */
    public function agendarMultiples(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_nombre' => 'required|string|min:3|max:100',
            'cliente_telefono' => 'required|string|size:10',
            'cliente_email' => 'nullable|email|max:100',
            'codigo_otp' => 'required|string|size:6',
            'servicios' => 'required|array|min:1',
            'servicios.*.servicio_id' => 'required|integer|exists:servicios,id',
            'servicios.*.empleado_id' => 'required|integer|exists:empleados,id',
            'servicios.*.fecha_hora' => 'required|date|after_or_equal:now',
            'tokens_reserva' => 'nullable|array',
            'tokens_reserva.*' => 'string',
            'notas' => 'nullable|string|max:500',
        ]);

        // Verificar OTP
        $otp = OtpCode::where('telefono', $request->cliente_telefono)
            ->where('tipo', 'agendamiento')
            ->where('expira_at', '>', now())
            ->first();

        if (config('app.debug')) {
            $otpDebug = OtpCode::where('telefono', $request->cliente_telefono)->first();
            Log::info('🔍 Buscando OTP para múltiples citas:', [
                'telefono' => $request->cliente_telefono,
                'codigo_enviado' => $request->codigo_otp,
                'otp_encontrado' => $otp ? 'Sí' : 'No',
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

        // Validar tokens de reserva temporal si se proporcionan
        $tokensReserva = $request->input('tokens_reserva', []);
        $reservasValidas = [];
        
        if (!empty($tokensReserva) && is_array($tokensReserva)) {
            foreach ($tokensReserva as $token) {
                $reserva = \App\Models\ReservaTemporal::obtenerPorToken($token);
                if ($reserva && $reserva->expira_at > now()) {
                    // Guardar todas las reservas válidas por empleado_id
                    if (!isset($reservasValidas[$reserva->empleado_id])) {
                        $reservasValidas[$reserva->empleado_id] = [];
                    }
                    $reservasValidas[$reserva->empleado_id][] = [
                        'token' => $token,
                        'reserva' => $reserva
                    ];
                }
            }
        }

        // Verificar disponibilidad de todos los slots
        foreach ($request->servicios as $servicio) {
            // Buscar token de reserva que coincida con este slot
            $tokenExcluir = null;
            $fechaHoraServicio = \Carbon\Carbon::parse($servicio['fecha_hora']);
            $fechaServicio = $fechaHoraServicio->format('Y-m-d');
            $horaServicio = $fechaHoraServicio->format('H:i');
            $duracionServicio = \App\Models\Servicio::find($servicio['servicio_id'])->duracion ?? 0;
            $horaFinServicio = $fechaHoraServicio->copy()->addMinutes($duracionServicio)->format('H:i');
            
            // Buscar token que coincida por empleado, fecha y solapamiento de horarios
            if (isset($reservasValidas[$servicio['empleado_id']])) {
                foreach ($reservasValidas[$servicio['empleado_id']] as $reservaData) {
                    $reserva = $reservaData['reserva'];
                    // Verificar si la fecha coincide
                    $fechaReserva = $reserva->fecha instanceof \Carbon\Carbon 
                        ? $reserva->fecha->format('Y-m-d')
                        : \Carbon\Carbon::parse($reserva->fecha)->format('Y-m-d');
                    
                    if ($fechaReserva === $fechaServicio) {
                        // Verificar solapamiento de horarios
                        if ($this->haySolapamientoHorarios($horaServicio, $horaFinServicio, $reserva->hora_inicio, $reserva->hora_fin)) {
                            $tokenExcluir = $reservaData['token'];
                            
                            if (config('app.debug')) {
                                Log::info('🔑 Token encontrado para excluir', [
                                    'empleado_id' => $servicio['empleado_id'],
                                    'fecha' => $fechaServicio,
                                    'hora' => $horaServicio,
                                    'token' => $tokenExcluir,
                                ]);
                            }
                            break;
                        }
                    }
                }
            }
            
            if (config('app.debug') && !$tokenExcluir && !empty($reservasValidas)) {
                Log::warning('⚠️ No se encontró token para excluir', [
                    'empleado_id' => $servicio['empleado_id'],
                    'fecha' => $fechaServicio,
                    'hora' => $horaServicio,
                    'reservas_disponibles' => isset($reservasValidas[$servicio['empleado_id']]) 
                        ? count($reservasValidas[$servicio['empleado_id']]) 
                        : 0,
                ]);
            }
            
            $disponibilidad = $this->disponibilidadService->verificarDisponibilidad(
                $servicio['empleado_id'],
                $servicio['fecha_hora'],
                [$servicio['servicio_id']],
                false, // ignorarAnticipacionMinima
                $tokenExcluir // token de reserva a excluir
            );

            if (!$disponibilidad['disponible']) {
                return response()->json([
                    'success' => false,
                    'message' => $disponibilidad['mensaje'] ?? 'Uno de los horarios no está disponible',
                ], 422);
            }
        }
        
        // Liberar las reservas temporales después de verificar (se van a agendar)
        if (!empty($tokensReserva)) {
            foreach ($tokensReserva as $token) {
                \App\Models\ReservaTemporal::liberar($token);
            }
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

            $citasCreadas = [];

            // Crear cada cita
            foreach ($request->servicios as $index => $servicioData) {
                $servicio = Servicio::find($servicioData['servicio_id']);
                
                if (!$servicio) {
                    throw new \Exception("Servicio no encontrado: {$servicioData['servicio_id']}");
                }

                // Crear cita
                $cita = Cita::create([
                    'cliente_id' => $cliente->id,
                    'empleado_id' => $servicioData['empleado_id'],
                    'fecha_hora' => $servicioData['fecha_hora'],
                    'duracion_total' => $servicio->duracion,
                    'estado' => 'pendiente',
                    'precio_total' => $servicio->precio,
                    'precio_final' => $servicio->precio,
                    'notas' => $index === 0 && $request->notas 
                        ? $request->notas . ' (Cita coordinada ' . ($index + 1) . ' de ' . count($request->servicios) . ')'
                        : 'Cita coordinada ' . ($index + 1) . ' de ' . count($request->servicios),
                ]);

                // Agregar servicio a la cita
                CitaServicio::create([
                    'cita_id' => $cita->id,
                    'servicio_id' => $servicio->id,
                    'precio_aplicado' => $servicio->precio,
                    'orden' => 1,
                ]);

                // Cargar relaciones
                $cita->load(['cliente', 'empleado.user', 'servicios.servicio']);

                $citasCreadas[] = [
                    'id' => $cita->id,
                    'fecha' => $cita->fecha_hora->format('Y-m-d'),
                    'hora' => $cita->fecha_hora->format('H:i'),
                    'fecha_texto' => $cita->fecha_hora->format('d/m/Y'),
                    'hora_texto' => $cita->fecha_hora->format('h:i A'),
                    'duracion_total' => $cita->duracion_total,
                    'estado' => $cita->estado,
                    'precio_final' => $cita->precio_final,
                    'empleado' => [
                        'id' => $cita->empleado->id,
                        'nombre' => $cita->empleado->user->nombre ?? 'Empleado',
                    ],
                    'servicios' => $cita->servicios->map(fn($cs) => [
                        'id' => $cs->servicio->id,
                        'nombre' => $cs->servicio->nombre,
                        'precio_aplicado' => $cs->precio_aplicado,
                        'duracion' => $cs->servicio->duracion,
                    ]),
                ];
            }

            // Eliminar OTP usado
            $otp->delete();

            DB::commit();

            // Calcular totales
            $precioTotal = array_sum(array_column($citasCreadas, 'precio_final'));
            $duracionTotal = array_sum(array_column($citasCreadas, 'duracion_total'));

            Log::info("✅ Citas coordinadas agendadas - Cliente: {$cliente->nombre}, Citas: " . count($citasCreadas));

            return response()->json([
                'success' => true,
                'message' => '¡Citas agendadas exitosamente!',
                'cliente' => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'telefono' => $cliente->telefono,
                ],
                'citas' => $citasCreadas,
                'resumen' => [
                    'total_citas' => count($citasCreadas),
                    'precio_total' => $precioTotal,
                    'duracion_total' => $duracionTotal,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al agendar citas múltiples: ' . $e->getMessage());
            
            if (config('app.debug')) {
                Log::error('Detalle:', [
                    'trace' => $e->getTraceAsString(),
                    'request' => $request->all(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al agendar las citas. Intenta de nuevo.',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Obtener mensaje de OTP desde plantilla de BD o usar por defecto
     */
    private function obtenerMensajeOtp(string $codigo, int $minutosExpiracion): string
    {
        // Buscar plantilla en BD
        $plantilla = PlantillaNotificacion::where('tipo', 'otp')
            ->where('medio', 'whatsapp')
            ->where('activo', true)
            ->first();

        if ($plantilla) {
            // Usar plantilla de BD
            $mensaje = $plantilla->contenido;
            $mensaje = str_replace('{{codigo_otp}}', $codigo, $mensaje);
            $mensaje = str_replace('{{expiracion_minutos}}', (string) $minutosExpiracion, $mensaje);
            return $mensaje;
        }

        // Plantilla por defecto si no existe en BD
        return "🔐 *Código de verificación*\n\n"
             . "Tu código es: *{$codigo}*\n\n"
             . "⏱️ Válido por {$minutosExpiracion} minutos.\n\n"
             . "⚠️ No compartas este código con nadie.";
    }
}

