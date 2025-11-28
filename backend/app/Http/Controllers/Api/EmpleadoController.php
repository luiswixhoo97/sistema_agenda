<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\User;
use App\Models\HorarioEmpleado;
use App\Models\BloqueoTiempo;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EmpleadoController extends Controller
{
    /**
     * Listado público de empleados
     * 
     * GET /api/publico/empleados
     */
    public function indexPublico(Request $request): JsonResponse
    {
        $query = Empleado::with(['user', 'servicios'])
            ->where('active', true)
            ->whereHas('user', fn($q) => $q->where('active', true));

        // Filtrar por servicio
        if ($request->has('servicio_id')) {
            $query->whereHas('servicios', fn($q) => $q->where('servicios.id', $request->servicio_id));
        }

        $empleados = $query->get();

        return response()->json([
            'success' => true,
            'data' => $empleados->map(fn($e) => [
                'id' => $e->id,
                'nombre' => $e->user->nombre,
                'foto' => $e->foto,
                'bio' => $e->bio,
                'especialidades' => $e->especialidades,
                'promedio_calificacion' => round($e->promedio_calificacion, 1),
                'activo' => $e->active && $e->user->active,
                'servicios' => $e->servicios->map(fn($s) => [
                    'id' => $s->id,
                    'nombre' => $s->nombre,
                ]),
            ]),
        ]);
    }

    /**
     * Listado de empleados (admin)
     * 
     * GET /api/admin/empleados
     */
    public function index(Request $request): JsonResponse
    {
        $query = Empleado::with(['user.role', 'servicios'])
            ->withCount('citas');

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $empleados = $query->get();

        return response()->json([
            'success' => true,
            'data' => $empleados->map(fn($e) => $this->formatearEmpleado($e)),
        ]);
    }

    /**
     * Ver empleado
     * 
     * GET /api/admin/empleados/{id}
     */
    public function show(int $id): JsonResponse
    {
        $empleado = Empleado::with(['user.role', 'servicios', 'horarios', 'calificaciones'])
            ->withCount('citas')
            ->find($id);

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
            ], 404);
        }

        $data = $this->formatearEmpleado($empleado);
        $data['horarios'] = $empleado->horarios->map(fn($h) => [
            'id' => $h->id,
            'dia_semana' => $h->dia_semana,
            'dia_nombre' => $h->nombre_dia,
            'hora_inicio' => $h->hora_inicio,
            'hora_fin' => $h->hora_fin,
            'active' => $h->active,
        ]);
        $data['servicios'] = $empleado->servicios->map(fn($s) => [
            'id' => $s->id,
            'nombre' => $s->nombre,
            'precio_estandar' => $s->precio,
            'precio_especial' => $s->pivot->precio_especial,
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Crear empleado
     * 
     * POST /api/admin/empleados
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'telefono' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'foto' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'especialidades' => 'nullable|string',
            'servicios' => 'nullable|array',
            'servicios.*' => 'integer|exists:servicios,id',
        ]);

        try {
            DB::beginTransaction();

            // Obtener rol de empleado
            $rolEmpleado = \App\Models\Role::where('nombre', 'empleado')->first();

            // Crear usuario
            $user = User::create([
                'role_id' => $rolEmpleado->id,
                'nombre' => $request->nombre,
                'email' => $request->email,
                'telefono' => $request->telefono,
                'password' => Hash::make($request->password),
                'active' => true,
            ]);

            // Crear empleado
            $empleado = Empleado::create([
                'user_id' => $user->id,
                'foto' => $request->foto,
                'bio' => $request->bio,
                'especialidades' => $request->especialidades,
                'active' => true,
            ]);

            // Asignar servicios
            if ($request->has('servicios')) {
                $empleado->servicios()->attach($request->servicios);
            }

            // Crear horarios por defecto (L-V 9:00-18:00)
            for ($dia = 1; $dia <= 5; $dia++) {
                HorarioEmpleado::create([
                    'empleado_id' => $empleado->id,
                    'dia_semana' => $dia,
                    'hora_inicio' => '09:00',
                    'hora_fin' => '18:00',
                    'active' => true,
                ]);
            }

            DB::commit();

            Auditoria::registrar('crear', 'empleados', $empleado->id, null, $empleado->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Empleado creado correctamente',
                'data' => $this->formatearEmpleado($empleado->load(['user', 'servicios'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear empleado: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar empleado
     * 
     * PUT /api/admin/empleados/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $empleado = Empleado::with('user')->find($id);

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
            ], 404);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $empleado->user_id,
            'telefono' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'foto' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'especialidades' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        $datosAnteriores = $empleado->toArray();

        // Actualizar usuario
        $datosUser = $request->only(['nombre', 'email', 'telefono']);
        if ($request->filled('password')) {
            $datosUser['password'] = Hash::make($request->password);
        }
        if (!empty($datosUser)) {
            $empleado->user->update($datosUser);
        }

        // Actualizar empleado
        $empleado->update($request->only(['foto', 'bio', 'especialidades', 'active']));

        // Actualizar estado del usuario si se desactiva el empleado
        if ($request->has('active')) {
            $empleado->user->update(['active' => $request->active]);
        }

        Auditoria::registrar('actualizar', 'empleados', $empleado->id, $datosAnteriores, $empleado->fresh()->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Empleado actualizado correctamente',
            'data' => $this->formatearEmpleado($empleado->fresh(['user', 'servicios'])),
        ]);
    }

    /**
     * Eliminar empleado
     * 
     * DELETE /api/admin/empleados/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $empleado = Empleado::withCount(['citas' => fn($q) => $q->where('estado', 'pendiente')])->find($id);

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
            ], 404);
        }

        // No permitir eliminar si tiene citas pendientes
        if ($empleado->citas_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un empleado con citas pendientes',
            ], 422);
        }

        $datosAnteriores = $empleado->toArray();
        
        // Soft delete del empleado y usuario
        $empleado->user->delete();
        $empleado->delete();

        Auditoria::registrar('eliminar', 'empleados', $id, $datosAnteriores, null);

        return response()->json([
            'success' => true,
            'message' => 'Empleado eliminado correctamente',
        ]);
    }

    /**
     * Obtener horarios del empleado
     * 
     * GET /api/admin/empleados/{id}/horarios
     */
    public function horarios(int $id): JsonResponse
    {
        $empleado = Empleado::with('horarios')->find($id);

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $empleado->horarios->map(fn($h) => [
                'id' => $h->id,
                'dia_semana' => $h->dia_semana,
                'dia_nombre' => $h->nombre_dia,
                'hora_inicio' => $h->hora_inicio,
                'hora_fin' => $h->hora_fin,
                'active' => $h->active,
            ]),
        ]);
    }

    /**
     * Actualizar horarios del empleado
     * 
     * PUT /api/admin/empleados/{id}/horarios
     */
    public function actualizarHorarios(Request $request, int $id): JsonResponse
    {
        $empleado = Empleado::find($id);

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
            ], 404);
        }

        $request->validate([
            'horarios' => 'required|array',
            'horarios.*.dia_semana' => 'required|integer|min:1|max:7',
            'horarios.*.active' => 'required',
        ]);
        
        // Normalizar y validar cada horario, crear nuevo array
        $horariosNormalizados = [];
        foreach ($request->horarios as $horarioData) {
            // Convertir active a boolean si viene como string
            $isActive = filter_var($horarioData['active'], FILTER_VALIDATE_BOOLEAN);
            
            if ($isActive) {
                // Validar horas para días activos
                if (empty($horarioData['hora_inicio']) || !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $horarioData['hora_inicio'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "La hora de inicio es requerida y debe tener formato HH:mm para el día {$horarioData['dia_semana']}",
                    ], 422);
                }
                if (empty($horarioData['hora_fin']) || !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $horarioData['hora_fin'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "La hora de fin es requerida y debe tener formato HH:mm para el día {$horarioData['dia_semana']}",
                    ], 422);
                }
                if ($horarioData['hora_fin'] <= $horarioData['hora_inicio']) {
                    return response()->json([
                        'success' => false,
                        'message' => "La hora de fin debe ser posterior a la hora de inicio para el día {$horarioData['dia_semana']}",
                    ], 422);
                }
            }
            
            // Agregar horario normalizado al nuevo array
            $horariosNormalizados[] = [
                'dia_semana' => $horarioData['dia_semana'],
                'active' => $isActive,
                'hora_inicio' => $isActive ? $horarioData['hora_inicio'] : '09:00',
                'hora_fin' => $isActive ? $horarioData['hora_fin'] : '18:00',
            ];
        }

        foreach ($horariosNormalizados as $horarioData) {
            HorarioEmpleado::updateOrCreate(
                [
                    'empleado_id' => $empleado->id,
                    'dia_semana' => $horarioData['dia_semana'],
                ],
                [
                    'hora_inicio' => $horarioData['active'] ? $horarioData['hora_inicio'] : '09:00',
                    'hora_fin' => $horarioData['active'] ? $horarioData['hora_fin'] : '18:00',
                    'active' => $horarioData['active'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Horarios actualizados correctamente',
        ]);
    }

    /**
     * Obtener servicios del empleado
     * 
     * GET /api/admin/empleados/{id}/servicios
     */
    public function servicios(int $id): JsonResponse
    {
        $empleado = Empleado::with('servicios')->find($id);

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $empleado->servicios->map(fn($s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'precio_estandar' => $s->precio,
                'precio_especial' => $s->pivot->precio_especial,
                'duracion_minutos' => $s->duracion, // El campo en la BD es 'duracion'
                'duracion' => $s->duracion, // También incluir como 'duracion' para compatibilidad
            ]),
        ]);
    }

    /**
     * Asignar servicios al empleado
     * 
     * PUT /api/admin/empleados/{id}/servicios
     */
    public function asignarServicios(Request $request, int $id): JsonResponse
    {
        $empleado = Empleado::find($id);

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
            ], 404);
        }

        $request->validate([
            'servicios' => 'required|array',
            'servicios.*.id' => 'required|integer|exists:servicios,id',
            'servicios.*.precio_especial' => 'nullable|numeric|min:0',
        ]);

        $serviciosData = [];
        foreach ($request->servicios as $servicio) {
            $serviciosData[$servicio['id']] = [
                'precio_especial' => $servicio['precio_especial'] ?? null,
            ];
        }

        $empleado->servicios()->sync($serviciosData);

        return response()->json([
            'success' => true,
            'message' => 'Servicios asignados correctamente',
        ]);
    }

    // =====================================================
    // RUTAS PARA EMPLEADOS (su propio perfil)
    // =====================================================

    /**
     * Mi perfil de empleado
     * 
     * GET /api/empleado/perfil
     */
    public function miPerfil(Request $request): JsonResponse
    {
        $user = $request->user();
        $empleado = $user->empleado;

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de empleado no encontrado',
            ], 404);
        }

        $empleado->load(['servicios', 'horarios']);

        return response()->json([
            'success' => true,
            'data' => $this->formatearEmpleado($empleado),
        ]);
    }

    /**
     * Mis estadísticas
     * 
     * GET /api/empleado/estadisticas
     */
    public function misEstadisticas(Request $request): JsonResponse
    {
        $user = $request->user();
        $empleado = $user->empleado;

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de empleado no encontrado',
            ], 404);
        }

        $hoy = now()->format('Y-m-d');
        $inicioSemana = now()->startOfWeek()->format('Y-m-d');
        $finSemana = now()->endOfWeek()->format('Y-m-d');
        $inicioMes = now()->startOfMonth()->format('Y-m-d');
        $finMes = now()->endOfMonth()->format('Y-m-d');

        // === HOY ===
        $citasHoyTotal = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereDate('fecha_hora', $hoy)
            ->whereNotIn('estado', ['cancelada'])
            ->count();

        $citasHoyCompletadas = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereDate('fecha_hora', $hoy)
            ->where('estado', 'completada')
            ->count();

        $citasHoyPendientes = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereDate('fecha_hora', $hoy)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->count();

        $ingresosHoy = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereDate('fecha_hora', $hoy)
            ->where('estado', 'completada')
            ->sum('precio_final');

        // === SEMANA ===
        $citasSemanaTotal = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereBetween('fecha_hora', [$inicioSemana, $finSemana . ' 23:59:59'])
            ->whereNotIn('estado', ['cancelada'])
            ->count();

        $citasSemanaCompletadas = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereBetween('fecha_hora', [$inicioSemana, $finSemana . ' 23:59:59'])
            ->where('estado', 'completada')
            ->count();

        $ingresosSemana = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereBetween('fecha_hora', [$inicioSemana, $finSemana . ' 23:59:59'])
            ->where('estado', 'completada')
            ->sum('precio_final');

        // === MES ===
        $citasMesTotal = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereBetween('fecha_hora', [$inicioMes, $finMes . ' 23:59:59'])
            ->whereNotIn('estado', ['cancelada'])
            ->count();

        $citasMesCompletadas = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereBetween('fecha_hora', [$inicioMes, $finMes . ' 23:59:59'])
            ->where('estado', 'completada')
            ->count();

        $ingresosMes = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereBetween('fecha_hora', [$inicioMes, $finMes . ' 23:59:59'])
            ->where('estado', 'completada')
            ->sum('precio_final');

        // === TOTALES ===
        $clientesAtendidos = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->where('estado', 'completada')
            ->distinct('cliente_id')
            ->count('cliente_id');

        $citasTotales = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->where('estado', 'completada')
            ->count();

        $noShows = \App\Models\Cita::where('empleado_id', $empleado->id)
            ->whereBetween('fecha_hora', [$inicioMes, $finMes . ' 23:59:59'])
            ->where('estado', 'no_show')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'hoy' => [
                    'total' => $citasHoyTotal,
                    'completadas' => $citasHoyCompletadas,
                    'pendientes' => $citasHoyPendientes,
                    'ingresos' => round($ingresosHoy, 2),
                ],
                'semana' => [
                    'total' => $citasSemanaTotal,
                    'completadas' => $citasSemanaCompletadas,
                    'ingresos' => round($ingresosSemana, 2),
                ],
                'mes' => [
                    'total' => $citasMesTotal,
                    'completadas' => $citasMesCompletadas,
                    'ingresos' => round($ingresosMes, 2),
                    'no_shows' => $noShows,
                ],
                'totales' => [
                    'clientes_atendidos' => $clientesAtendidos,
                    'citas_completadas' => $citasTotales,
                ],
            ],
        ]);
    }

    /**
     * Actualizar mi perfil
     * 
     * PUT /api/empleado/perfil
     */
    public function actualizarMiPerfil(Request $request): JsonResponse
    {
        $user = $request->user();
        $empleado = $user->empleado;

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de empleado no encontrado',
            ], 404);
        }

        $request->validate([
            'foto' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
        ]);

        $empleado->update($request->only(['foto', 'bio']));

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
        ]);
    }

    /**
     * Mis horarios
     * 
     * GET /api/empleado/horarios
     */
    public function misHorarios(Request $request): JsonResponse
    {
        $user = $request->user();
        $empleado = $user->empleado;

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil de empleado no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $empleado->horarios->map(fn($h) => [
                'dia_semana' => $h->dia_semana,
                'dia_nombre' => $h->nombre_dia,
                'hora_inicio' => $h->hora_inicio,
                'hora_fin' => $h->hora_fin,
                'active' => $h->active,
            ]),
        ]);
    }

    /**
     * Mis bloqueos
     * 
     * GET /api/empleado/bloqueos
     */
    public function misBloqueos(Request $request): JsonResponse
    {
        $user = $request->user();
        $empleado = $user->empleado;

        $bloqueos = BloqueoTiempo::where('empleado_id', $empleado->id)
            ->where('fecha', '>=', now()->format('Y-m-d'))
            ->orderBy('fecha')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bloqueos->map(fn($b) => [
                'id' => $b->id,
                'fecha' => $b->fecha->format('Y-m-d'),
                'hora_inicio' => $b->hora_inicio,
                'hora_fin' => $b->hora_fin,
                'motivo' => $b->motivo,
                'tipo' => $b->tipo,
            ]),
        ]);
    }

    /**
     * Crear bloqueo
     * 
     * POST /api/empleado/bloqueos
     */
    public function crearBloqueo(Request $request): JsonResponse
    {
        $user = $request->user();
        $empleado = $user->empleado;

        $request->validate([
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'motivo' => 'nullable|string|max:255',
            'tipo' => 'required|in:almuerzo,limpieza,libre,reunion,otro',
        ]);

        $bloqueo = BloqueoTiempo::create([
            'empleado_id' => $empleado->id,
            'fecha' => $request->fecha,
            'hora_inicio' => $request->hora_inicio,
            'hora_fin' => $request->hora_fin,
            'motivo' => $request->motivo,
            'tipo' => $request->tipo,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bloqueo creado correctamente',
            'data' => [
                'id' => $bloqueo->id,
                'fecha' => $bloqueo->fecha->format('Y-m-d'),
                'hora_inicio' => $bloqueo->hora_inicio,
                'hora_fin' => $bloqueo->hora_fin,
                'tipo' => $bloqueo->tipo,
            ],
        ], 201);
    }

    /**
     * Eliminar bloqueo
     * 
     * DELETE /api/empleado/bloqueos/{id}
     */
    public function eliminarBloqueo(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $empleado = $user->empleado;

        $bloqueo = BloqueoTiempo::where('id', $id)
            ->where('empleado_id', $empleado->id)
            ->first();

        if (!$bloqueo) {
            return response()->json([
                'success' => false,
                'message' => 'Bloqueo no encontrado',
            ], 404);
        }

        $bloqueo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bloqueo eliminado correctamente',
        ]);
    }

    /**
     * Formatear empleado para respuesta
     */
    private function formatearEmpleado(Empleado $empleado): array
    {
        $data = [
            'id' => $empleado->id,
            'user_id' => $empleado->user_id,
            'nombre' => $empleado->user->nombre,
            'email' => $empleado->user->email,
            'telefono' => $empleado->user->telefono,
            'foto' => $empleado->foto,
            'bio' => $empleado->bio,
            'especialidades' => $empleado->especialidades,
            'active' => $empleado->active,
            'promedio_calificacion' => round($empleado->promedio_calificacion, 1),
            'citas_count' => $empleado->citas_count ?? 0,
        ];

        // Servicios con más detalle
        if ($empleado->relationLoaded('servicios')) {
            $data['servicios'] = $empleado->servicios->map(fn($s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'precio' => $s->precio,
                'duracion_minutos' => $s->duracion_minutos,
            ]);
        }

        // Horarios
        if ($empleado->relationLoaded('horarios')) {
            $data['horarios'] = $empleado->horarios->map(fn($h) => [
                'dia_semana' => $h->dia_semana,
                'dia_nombre' => $h->nombre_dia,
                'hora_inicio' => $h->hora_inicio,
                'hora_fin' => $h->hora_fin,
                'activo' => $h->active,
            ]);
        }

        return $data;
    }
}

