<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DisponibilidadService;
use App\Models\ReservaTemporal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DisponibilidadController extends Controller
{
    public function __construct(
        private DisponibilidadService $disponibilidadService
    ) {}

    /**
     * Obtener días con disponibilidad en un mes
     * 
     * GET /api/cliente/disponibilidad/dias
     * Query params: empleado_id, mes, anio, servicios[]
     */
    public function diasDisponibles(Request $request): JsonResponse
    {
        $request->validate([
            'empleado_id' => 'required|integer|exists:empleados,id',
            'mes' => 'required|integer|min:1|max:12',
            'anio' => 'required|integer|min:2024|max:2030',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
        ]);

        try {
            $dias = $this->disponibilidadService->obtenerDiasConDisponibilidad(
                $request->empleado_id,
                $request->mes,
                $request->anio,
                $request->servicios
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'empleado_id' => $request->empleado_id,
                    'mes' => $request->mes,
                    'anio' => $request->anio,
                    'dias' => $dias,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener disponibilidad: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener slots disponibles para una fecha (empleado - sin restricción de anticipación)
     * 
     * GET /api/empleado/disponibilidad/slots
     * Query params: empleado_id, fecha, servicios[]
     */
    public function slotsDisponiblesEmpleado(Request $request): JsonResponse
    {
        $request->validate([
            'empleado_id' => 'required|integer|exists:empleados,id',
            'fecha' => 'required|date|date_format:Y-m-d',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
        ]);

        try {
            // Para empleados, siempre ignorar la anticipación mínima
            $resultado = $this->disponibilidadService->obtenerSlotsDisponibles(
                $request->empleado_id,
                $request->fecha,
                $request->servicios,
                true // Siempre ignorar anticipación mínima para empleados
            );

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener slots: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener slots disponibles para una fecha
     * 
     * GET /api/cliente/disponibilidad/slots
     * Query params: empleado_id, fecha, servicios[]
     */
    public function slotsDisponibles(Request $request): JsonResponse
    {
        $request->validate([
            'empleado_id' => 'required|integer|exists:empleados,id',
            'fecha' => 'required|date|date_format:Y-m-d',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
        ]);

        try {
            // Si el usuario autenticado es un empleado y está consultando sus propios slots,
            // ignorar la anticipación mínima para permitirle crear citas inmediatas
            $ignorarAnticipacion = false;
            if (auth()->check()) {
                $user = auth()->user();
                if ($user instanceof \App\Models\User) {
                    $empleado = \App\Models\Empleado::where('user_id', $user->id)->first();
                    if ($empleado && $empleado->id == $request->empleado_id) {
                        $ignorarAnticipacion = true;
                        \Log::info('Ignorando anticipación mínima para empleado', [
                            'empleado_id' => $empleado->id,
                            'user_id' => $user->id,
                            'fecha' => $request->fecha
                        ]);
                    }
                }
            }
            
            \Log::info('Obteniendo slots disponibles', [
                'empleado_id' => $request->empleado_id,
                'fecha' => $request->fecha,
                'ignorar_anticipacion' => $ignorarAnticipacion
            ]);
            
            $resultado = $this->disponibilidadService->obtenerSlotsDisponibles(
                $request->empleado_id,
                $request->fecha,
                $request->servicios,
                $ignorarAnticipacion
            );

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener slots: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verificar disponibilidad específica
     * 
     * POST /api/cliente/disponibilidad/verificar
     * Body: empleado_id, fecha_hora, servicios[]
     */
    public function verificar(Request $request): JsonResponse
    {
        $request->validate([
            'empleado_id' => 'required|integer|exists:empleados,id',
            'fecha_hora' => 'required|date',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
        ]);

        try {
            $resultado = $this->disponibilidadService->verificarDisponibilidad(
                $request->empleado_id,
                $request->fecha_hora,
                $request->servicios
            );

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar disponibilidad: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener empleados disponibles para servicios en fecha/hora
     * 
     * GET /api/cliente/disponibilidad/empleados
     * Query params: fecha_hora, servicios[]
     */
    public function empleadosDisponibles(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_hora' => 'required|date',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
        ]);

        try {
            $empleados = $this->disponibilidadService->obtenerEmpleadosDisponibles(
                $request->servicios,
                $request->fecha_hora
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'fecha_hora' => $request->fecha_hora,
                    'empleados' => $empleados->map(fn($e) => [
                        'id' => $e->id,
                        'nombre' => $e->user->nombre,
                        'foto' => $e->foto,
                        'bio' => $e->bio,
                        'promedio_calificacion' => $e->promedio_calificacion,
                    ])->values(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener empleados: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener slots coordinados para múltiples servicios con diferentes empleados
     * 
     * POST /api/publico/disponibilidad/slots-coordinados
     * Body: { fecha, servicios: [{ servicio_id, empleado_id }, ...] }
     */
    public function slotsCoordinados(Request $request): JsonResponse
    {
        $request->validate([
            'fecha' => 'required|date|date_format:Y-m-d',
            'servicios' => 'required|array|min:1',
            'servicios.*.servicio_id' => 'required|integer|exists:servicios,id',
            'servicios.*.empleado_id' => 'required|integer|exists:empleados,id',
        ]);

        try {
            $resultado = $this->disponibilidadService->obtenerSlotsCoordinados(
                $request->servicios,
                $request->fecha
            );

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener slots coordinados: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calcular precio y duración de servicios
     * 
     * POST /api/cliente/disponibilidad/calcular
     * Body: servicios[], empleado_id (opcional)
     */
    public function calcularServicio(Request $request): JsonResponse
    {
        $request->validate([
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
            'empleado_id' => 'nullable|integer|exists:empleados,id',
        ]);

        try {
            $duracion = $this->disponibilidadService->calcularDuracionTotal($request->servicios);
            $precio = $this->disponibilidadService->calcularPrecioTotal(
                $request->servicios,
                $request->empleado_id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'duracion_total' => $duracion,
                    'duracion_texto' => $this->formatearDuracion($duracion),
                    'precio_total' => $precio,
                    'precio_texto' => '$' . number_format($precio, 2),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Formatear duración en texto legible
     */
    private function formatearDuracion(int $minutos): string
    {
        if ($minutos < 60) {
            return "{$minutos} min";
        }

        $horas = floor($minutos / 60);
        $mins = $minutos % 60;

        if ($mins === 0) {
            return "{$horas}h";
        }

        return "{$horas}h {$mins}min";
    }

    /**
     * Reservar temporalmente un slot de horario
     * Esto "aparta" el horario por X minutos mientras el usuario completa la reserva
     * 
     * POST /api/publico/disponibilidad/reservar-temporal
     */
    public function reservarTemporal(Request $request): JsonResponse
    {
        $request->validate([
            'empleado_id' => 'required|integer|exists:empleados,id',
            'fecha' => 'required|date|date_format:Y-m-d',
            'hora' => 'required|string',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
            'telefono' => 'nullable|string|max:20',
            'session_id' => 'nullable|string|max:100',
        ]);

        try {
            // Calcular duración total
            $duracionTotal = $this->disponibilidadService->calcularDuracionTotal($request->servicios);
            
            // Calcular hora de fin
            $horaInicio = $request->hora;
            $horaFin = \Carbon\Carbon::parse($request->fecha . ' ' . $horaInicio)
                ->addMinutes($duracionTotal)
                ->format('H:i');

            // Verificar que el slot no esté ya reservado temporalmente
            if (ReservaTemporal::slotReservado(
                $request->empleado_id,
                $request->fecha,
                $horaInicio,
                $horaFin
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este horario acaba de ser reservado por otro usuario. Por favor, selecciona otro.',
                    'codigo' => 'SLOT_YA_RESERVADO',
                ], 409); // Conflict
            }

            // Verificar disponibilidad real (citas existentes)
            $disponibilidad = $this->disponibilidadService->verificarDisponibilidad(
                $request->empleado_id,
                $request->fecha . ' ' . $horaInicio . ':00',
                $request->servicios
            );

            if (!$disponibilidad['disponible']) {
                return response()->json([
                    'success' => false,
                    'message' => $disponibilidad['mensaje'] ?? 'El horario ya no está disponible',
                    'codigo' => 'SLOT_NO_DISPONIBLE',
                ], 409);
            }

            // Crear reserva temporal
            $reserva = ReservaTemporal::reservar([
                'empleado_id' => $request->empleado_id,
                'fecha' => $request->fecha,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'duracion_total' => $duracionTotal,
                'telefono_cliente' => $request->telefono,
                'session_id' => $request->session_id,
                'servicios_ids' => $request->servicios,
            ]);

            Log::info('🔒 Slot reservado temporalmente', [
                'token' => $reserva->token,
                'empleado_id' => $request->empleado_id,
                'fecha' => $request->fecha,
                'hora' => $horaInicio,
                'expira_at' => $reserva->expira_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Horario reservado temporalmente',
                'data' => [
                    'token' => $reserva->token,
                    'expira_at' => $reserva->expira_at->toIso8601String(),
                    'tiempo_restante_segundos' => $reserva->tiempoRestante(),
                    'fecha' => $reserva->fecha->format('Y-m-d'),
                    'hora_inicio' => $reserva->hora_inicio,
                    'hora_fin' => $reserva->hora_fin,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error al reservar slot temporal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al reservar el horario',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Reservar temporalmente múltiples slots coordinados
     * 
     * POST /api/publico/disponibilidad/reservar-temporal-multiple
     */
    public function reservarTemporalMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'fecha' => 'required|date|date_format:Y-m-d',
            'servicios' => 'required|array|min:1',
            'servicios.*.servicio_id' => 'required|integer|exists:servicios,id',
            'servicios.*.empleado_id' => 'required|integer|exists:empleados,id',
            'servicios.*.hora_inicio' => 'required|string',
            'servicios.*.hora_fin' => 'required|string',
            'telefono' => 'nullable|string|max:20',
            'session_id' => 'nullable|string|max:100',
        ]);

        try {
            $reservas = [];
            $tokensCreados = [];

            // Verificar todos los slots primero
            foreach ($request->servicios as $servicio) {
                if (ReservaTemporal::slotReservado(
                    $servicio['empleado_id'],
                    $request->fecha,
                    $servicio['hora_inicio'],
                    $servicio['hora_fin']
                )) {
                    // Liberar reservas creadas hasta ahora
                    foreach ($tokensCreados as $token) {
                        ReservaTemporal::liberar($token);
                    }

                    return response()->json([
                        'success' => false,
                        'message' => 'Uno de los horarios acaba de ser reservado. Por favor, selecciona otro horario.',
                        'codigo' => 'SLOT_YA_RESERVADO',
                    ], 409);
                }
            }

            // Calcular duración total de cada servicio
            $servicioIds = array_column($request->servicios, 'servicio_id');
            $serviciosModels = \App\Models\Servicio::whereIn('id', $servicioIds)->get()->keyBy('id');

            // Crear reservas para cada slot
            foreach ($request->servicios as $servicio) {
                $servicioModel = $serviciosModels->get($servicio['servicio_id']);
                $duracion = $servicioModel ? $servicioModel->duracion : 30;

                $reserva = ReservaTemporal::reservar([
                    'empleado_id' => $servicio['empleado_id'],
                    'fecha' => $request->fecha,
                    'hora_inicio' => $servicio['hora_inicio'],
                    'hora_fin' => $servicio['hora_fin'],
                    'duracion_total' => $duracion,
                    'telefono_cliente' => $request->telefono,
                    'session_id' => $request->session_id,
                    'servicios_ids' => [$servicio['servicio_id']],
                    'datos_adicionales' => [
                        'es_multiple' => true,
                        'total_servicios' => count($request->servicios),
                    ],
                ]);

                $tokensCreados[] = $reserva->token;
                $reservas[] = [
                    'token' => $reserva->token,
                    'empleado_id' => $servicio['empleado_id'],
                    'servicio_id' => $servicio['servicio_id'],
                    'hora_inicio' => $servicio['hora_inicio'],
                    'hora_fin' => $servicio['hora_fin'],
                ];
            }

            // Obtener datos de la primera reserva para el tiempo de expiración
            $primeraReserva = ReservaTemporal::obtenerPorToken($tokensCreados[0]);

            Log::info('🔒 Slots múltiples reservados temporalmente', [
                'tokens' => $tokensCreados,
                'fecha' => $request->fecha,
                'total_servicios' => count($request->servicios),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Horarios reservados temporalmente',
                'data' => [
                    'tokens' => $tokensCreados,
                    'reservas' => $reservas,
                    'expira_at' => $primeraReserva->expira_at->toIso8601String(),
                    'tiempo_restante_segundos' => $primeraReserva->tiempoRestante(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error al reservar slots múltiples: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al reservar los horarios',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Liberar una reserva temporal
     * 
     * POST /api/publico/disponibilidad/liberar-temporal
     */
    public function liberarTemporal(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $liberado = ReservaTemporal::liberar($request->token);

            if ($liberado) {
                Log::info('🔓 Reserva temporal liberada', ['token' => $request->token]);
            }

            return response()->json([
                'success' => true,
                'message' => $liberado ? 'Reserva liberada' : 'La reserva ya no existía',
            ]);

        } catch (\Exception $e) {
            Log::error('Error al liberar reserva: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al liberar la reserva',
            ], 500);
        }
    }

    /**
     * Liberar múltiples reservas temporales
     * 
     * POST /api/publico/disponibilidad/liberar-temporal-multiple
     */
    public function liberarTemporalMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'tokens' => 'required|array|min:1',
            'tokens.*' => 'string',
        ]);

        try {
            $liberados = 0;
            foreach ($request->tokens as $token) {
                if (ReservaTemporal::liberar($token)) {
                    $liberados++;
                }
            }

            Log::info('🔓 Reservas múltiples liberadas', [
                'total' => count($request->tokens),
                'liberados' => $liberados,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Se liberaron {$liberados} reservas",
                'liberados' => $liberados,
            ]);

        } catch (\Exception $e) {
            Log::error('Error al liberar reservas múltiples: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al liberar las reservas',
            ], 500);
        }
    }

    /**
     * Extender el tiempo de una reserva temporal
     * 
     * POST /api/publico/disponibilidad/extender-temporal
     */
    public function extenderTemporal(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        try {
            $reserva = ReservaTemporal::obtenerPorToken($request->token);

            if (!$reserva) {
                return response()->json([
                    'success' => false,
                    'message' => 'La reserva ha expirado o no existe',
                    'codigo' => 'RESERVA_EXPIRADA',
                ], 404);
            }

            $reserva->extender();

            return response()->json([
                'success' => true,
                'message' => 'Tiempo de reserva extendido',
                'data' => [
                    'expira_at' => $reserva->expira_at->toIso8601String(),
                    'tiempo_restante_segundos' => $reserva->tiempoRestante(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error al extender reserva: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al extender la reserva',
            ], 500);
        }
    }

    /**
     * Verificar estado de una reserva temporal
     * 
     * GET /api/publico/disponibilidad/verificar-reserva/{token}
     */
    public function verificarReserva(string $token): JsonResponse
    {
        $reserva = ReservaTemporal::obtenerPorToken($token);

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'activa' => false,
                'message' => 'La reserva ha expirado o no existe',
            ]);
        }

        return response()->json([
            'success' => true,
            'activa' => true,
            'data' => [
                'expira_at' => $reserva->expira_at->toIso8601String(),
                'tiempo_restante_segundos' => $reserva->tiempoRestante(),
            ],
        ]);
    }
}

