<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\FotoCita;
use App\Services\CitaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CitaController extends Controller
{
    public function __construct(
        private CitaService $citaService
    ) {}

    // =====================================================
    // RUTAS PARA CLIENTES
    // =====================================================

    /**
     * Obtener citas del cliente autenticado
     * 
     * GET /api/cliente/citas
     */
    public function misCitas(Request $request): JsonResponse
    {
        $cliente = $this->getClienteAutenticado($request);
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como cliente',
            ], 401);
        }

        $query = Cita::with(['empleado.user', 'servicio', 'servicios.servicio'])
            ->where('cliente_id', $cliente->id)
            ->whereNull('deleted_at');

        // Filtrar por estado
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtrar por fecha
        if ($request->has('desde')) {
            $query->where('fecha_hora', '>=', $request->desde);
        }
        if ($request->has('hasta')) {
            $query->where('fecha_hora', '<=', $request->hasta);
        }

        // Por defecto, mostrar citas futuras primero
        $query->orderBy('fecha_hora', $request->get('orden', 'asc'));

        $citas = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'citas' => $citas->map(fn($c) => $this->citaService->formatearCita($c)),
                'pagination' => [
                    'current_page' => $citas->currentPage(),
                    'last_page' => $citas->lastPage(),
                    'per_page' => $citas->perPage(),
                    'total' => $citas->total(),
                ],
            ],
        ]);
    }

    /**
     * Ver detalle de una cita del cliente
     * 
     * GET /api/cliente/citas/{id}
     */
    public function verMiCita(Request $request, int $id): JsonResponse
    {
        $cliente = $this->getClienteAutenticado($request);
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como cliente',
            ], 401);
        }

        $cita = Cita::with(['empleado.user', 'servicio', 'servicios.servicio', 'fotos', 'promocion'])
            ->where('id', $id)
            ->where('cliente_id', $cliente->id)
            ->first();

        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada',
            ], 404);
        }

        $citaFormateada = $this->citaService->formatearCita($cita);
        $citaFormateada['fotos'] = $cita->fotos->map(fn($f) => [
            'id' => $f->id,
            'tipo' => $f->tipo,
            'url' => $f->url,
            'descripcion' => $f->descripcion,
        ]);

        if ($cita->promocion) {
            $citaFormateada['promocion'] = [
                'id' => $cita->promocion->id,
                'nombre' => $cita->promocion->nombre,
                'descuento' => $cita->promocion->descuento_porcentaje 
                    ? "{$cita->promocion->descuento_porcentaje}%" 
                    : "\${$cita->promocion->descuento_fijo}",
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $citaFormateada,
        ]);
    }

    /**
     * Agendar nueva cita
     * 
     * POST /api/cliente/citas
     */
    public function agendar(Request $request): JsonResponse
    {
        $cliente = $this->getClienteAutenticado($request);
        
        if (!$cliente) {
            // Debug: mostrar qué tipo de usuario tenemos
            $user = $request->user();
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como cliente',
                'debug' => config('app.debug') ? [
                    'user_type' => $user ? get_class($user) : null,
                    'user_id' => $user?->id,
                ] : null,
            ], 401);
        }

        $request->validate([
            'empleado_id' => 'required|integer|exists:empleados,id',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
            'fecha_hora' => 'required|date|after_or_equal:today',
            'promocion_id' => 'nullable|integer|exists:promociones,id',
            'notas' => 'nullable|string|max:500',
        ]);

        $resultado = $this->citaService->agendar($request->all(), $cliente->id);

        return response()->json($resultado, $resultado['success'] ? 201 : 422);
    }

    /**
     * Modificar cita del cliente
     * 
     * PUT /api/cliente/citas/{id}
     */
    public function modificarMiCita(Request $request, int $id): JsonResponse
    {
        $cliente = $this->getClienteAutenticado($request);
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como cliente',
            ], 401);
        }

        $request->validate([
            'fecha_hora' => 'nullable|date|after:now',
            'notas' => 'nullable|string|max:500',
        ]);

        $resultado = $this->citaService->modificar($id, $request->all(), $cliente->id);

        return response()->json($resultado, $resultado['success'] ? 200 : 422);
    }

    /**
     * Cancelar cita del cliente
     * 
     * POST /api/cliente/citas/{id}/cancelar
     */
    public function cancelarMiCita(Request $request, int $id): JsonResponse
    {
        $cliente = $this->getClienteAutenticado($request);
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como cliente',
            ], 401);
        }

        $request->validate([
            'motivo' => 'nullable|string|max:255',
        ]);

        $resultado = $this->citaService->cancelar($id, $cliente->id, $request->motivo);

        return response()->json($resultado, $resultado['success'] ? 200 : 422);
    }

    /**
     * Calificar una cita
     * 
     * POST /api/cliente/citas/{id}/calificar
     */
    public function calificar(Request $request, int $id): JsonResponse
    {
        $cliente = $this->getClienteAutenticado($request);
        
        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como cliente',
            ], 401);
        }

        $request->validate([
            'puntuacion' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500',
        ]);

        $cita = Cita::where('id', $id)
            ->where('cliente_id', $cliente->id)
            ->where('estado', Cita::ESTADO_COMPLETADA)
            ->first();

        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes calificar citas completadas',
            ], 422);
        }

        // Verificar que no haya calificación previa
        if ($cita->calificacion()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya has calificado esta cita',
            ], 422);
        }

        $cita->calificacion()->create([
            'cliente_id' => $cliente->id,
            'empleado_id' => $cita->empleado_id,
            'puntuacion' => $request->puntuacion,
            'comentario' => $request->comentario,
            'visible' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Gracias por tu calificación!',
        ]);
    }

    // =====================================================
    // RUTAS PARA EMPLEADOS
    // =====================================================

    /**
     * Citas del día para el empleado
     * 
     * GET /api/empleado/calendario/dia
     */
    public function citasDelDia(Request $request): JsonResponse
    {
        $empleado = $this->getEmpleadoAutenticado($request);
        
        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como empleado',
            ], 401);
        }

        $fecha = $request->get('fecha', now()->format('Y-m-d'));

        // El calendario muestra TODAS las citas del día (sin filtro de 15 min)
        // Excluimos canceladas y reagendadas
        $citas = Cita::with(['cliente', 'servicio', 'servicios.servicio'])
            ->where('empleado_id', $empleado->id)
            ->whereDate('fecha_hora', $fecha)
            ->whereNotIn('estado', [Cita::ESTADO_CANCELADA, Cita::ESTADO_REAGENDADA])
            ->orderBy('fecha_hora', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'fecha' => $fecha,
                'citas' => $citas->map(fn($c) => $this->citaService->formatearCita($c)),
            ],
        ]);
    }

    /**
     * Citas de la semana para el empleado
     * 
     * GET /api/empleado/calendario/semana
     */
    public function citasDeLaSemana(Request $request): JsonResponse
    {
        $empleado = $this->getEmpleadoAutenticado($request);
        
        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como empleado',
            ], 401);
        }

        $fecha = Carbon::parse($request->get('fecha', now()));
        $inicioSemana = $fecha->copy()->startOfWeek();
        $finSemana = $fecha->copy()->endOfWeek();

        $citas = Cita::with(['cliente', 'servicio', 'servicios.servicio'])
            ->where('empleado_id', $empleado->id)
            ->whereBetween('fecha_hora', [$inicioSemana, $finSemana])
            ->whereNotIn('estado', [Cita::ESTADO_CANCELADA, Cita::ESTADO_REAGENDADA])
            ->orderBy('fecha_hora')
            ->get();

        // Agrupar por día
        $citasPorDia = $citas->groupBy(fn($c) => $c->fecha_hora->format('Y-m-d'));

        return response()->json([
            'success' => true,
            'data' => [
                'inicio_semana' => $inicioSemana->format('Y-m-d'),
                'fin_semana' => $finSemana->format('Y-m-d'),
                'citas_por_dia' => $citasPorDia->map(fn($grupo) => 
                    $grupo->map(fn($c) => $this->citaService->formatearCita($c))
                ),
            ],
        ]);
    }

    /**
     * Mis citas como empleado
     * 
     * GET /api/empleado/citas
     */
    public function misCitasEmpleado(Request $request): JsonResponse
    {
        $empleado = $this->getEmpleadoAutenticado($request);
        
        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como empleado',
            ], 401);
        }

        $query = Cita::with(['cliente', 'servicio', 'servicios.servicio'])
            ->where('empleado_id', $empleado->id);

        // Si hay un filtro de estado específico o de fecha, permitir ver reagendadas y canceladas
        // Si no hay filtro, excluir canceladas y reagendadas (solo citas activas)
        $tieneFiltroEspecifico = $request->has('estado') || $request->has('desde') || $request->has('hasta');
        
        if (!$tieneFiltroEspecifico) {
            $query->whereNotIn('estado', [Cita::ESTADO_CANCELADA, Cita::ESTADO_REAGENDADA]);
        }

        // Filtro de tolerancia: mostrar solo citas que no hayan pasado hace más de 15 min
        // Esto da tolerancia a los clientes que llegan tarde
        // NO tomamos en cuenta el estado, solo la hora real
        // EXCEPTO si se está filtrando por estado o fecha específica (para ver historial)
        if (!$tieneFiltroEspecifico) {
            $limiteHora = now()->subMinutes(15);
            
            // Log para debug
            \Log::info('Mis Citas - Hora actual: ' . now() . ' | Limite: ' . $limiteHora);
            
            $query->where('fecha_hora', '>=', $limiteHora);
        }

        // Filtros adicionales
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->has('desde')) {
            $query->where('fecha_hora', '>=', $request->desde);
        }
        if ($request->has('hasta')) {
            $query->where('fecha_hora', '<=', $request->hasta);
        }

        // Ordenar por fecha/hora ascendente (más temprana primero)
        $citas = $query->orderBy('fecha_hora', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'citas' => $citas->map(fn($c) => $this->citaService->formatearCita($c)),
            ],
        ]);
    }

    /**
     * Cambiar estado de una cita (empleado)
     * 
     * PUT /api/empleado/citas/{id}/estado
     */
    public function cambiarEstado(Request $request, int $id): JsonResponse
    {
        $empleado = $this->getEmpleadoAutenticado($request);
        
        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como empleado',
            ], 401);
        }

        $request->validate([
            'estado' => 'required|in:confirmada,en_proceso,completada,no_show',
        ]);

        $resultado = $this->citaService->cambiarEstado($id, $request->estado, $empleado->id);

        return response()->json($resultado, $resultado['success'] ? 200 : 422);
    }

    /**
     * Reagendar una cita (empleado)
     * Marca la cita original como "reagendada" y crea una nueva cita
     * 
     * POST /api/empleado/citas/{id}/reagendar
     */
    public function reagendarEmpleado(Request $request, int $id): JsonResponse
    {
        \Log::info('reagendarEmpleado - Request recibido', [
            'id' => $id,
            'all' => $request->all(),
        ]);

        $empleado = $this->getEmpleadoAutenticado($request);
        
        \Log::info('Empleado autenticado', ['empleado' => $empleado ? $empleado->toArray() : null]);

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como empleado',
            ], 401);
        }

        $request->validate([
            'fecha_hora' => 'required|string',
            'motivo' => 'nullable|string|max:500',
        ]);

        // Parsear y validar fecha
        try {
            $fechaHora = \Carbon\Carbon::parse($request->fecha_hora);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Formato de fecha inválido',
            ], 422);
        }

        // Validar que la fecha sea futura
        if ($fechaHora->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha y hora deben ser en el futuro',
            ], 422);
        }

        // Formatear correctamente para el servicio
        $fechaHoraFormateada = $fechaHora->format('Y-m-d H:i:s');

        \Log::info('Fecha formateada', ['fechaHora' => $fechaHoraFormateada]);

        $resultado = $this->citaService->reagendar(
            $id,
            $fechaHoraFormateada,
            $request->motivo,
            $empleado->id // Solo puede reagendar sus propias citas
        );

        return response()->json($resultado, $resultado['success'] ? 200 : 422);
    }

    /**
     * Cancelar una cita (empleado)
     * 
     * POST /api/empleado/citas/{id}/cancelar
     */
    public function cancelarEmpleado(Request $request, int $id): JsonResponse
    {
        $empleado = $this->getEmpleadoAutenticado($request);
        
        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como empleado',
            ], 401);
        }

        $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        // Cambiar estado a cancelada
        $resultado = $this->citaService->cambiarEstado($id, Cita::ESTADO_CANCELADA, $empleado->id);

        return response()->json($resultado, $resultado['success'] ? 200 : 422);
    }

    /**
     * Subir foto a una cita
     * 
     * POST /api/empleado/citas/{id}/fotos
     */
    public function subirFoto(Request $request, int $id): JsonResponse
    {
        $empleado = $this->getEmpleadoAutenticado($request);
        
        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como empleado',
            ], 401);
        }

        $request->validate([
            'foto' => 'required|image|max:5120', // 5MB máximo
            'tipo' => 'required|in:antes,despues,proceso',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $cita = Cita::where('id', $id)
            ->where('empleado_id', $empleado->id)
            ->first();

        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada',
            ], 404);
        }

        // Guardar archivo
        $path = $request->file('foto')->store('fotos_citas/' . $cita->id, 'public');

        $foto = FotoCita::create([
            'cita_id' => $cita->id,
            'tipo' => $request->tipo,
            'ruta_archivo' => $path,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto subida correctamente',
            'foto' => [
                'id' => $foto->id,
                'tipo' => $foto->tipo,
                'url' => $foto->url,
                'descripcion' => $foto->descripcion,
            ],
        ], 201);
    }

    // =====================================================
    // RUTAS PARA ADMIN
    // =====================================================

    /**
     * Listar todas las citas (admin)
     * 
     * GET /api/admin/citas
     */
    public function index(Request $request): JsonResponse
    {
        $query = Cita::with(['cliente', 'empleado.user', 'servicio', 'servicios.servicio']);

        // Filtros
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->has('empleado_id')) {
            $query->where('empleado_id', $request->empleado_id);
        }
        if ($request->has('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->has('desde')) {
            $query->where('fecha_hora', '>=', $request->desde);
        }
        if ($request->has('hasta')) {
            $query->where('fecha_hora', '<=', $request->hasta);
        }

        $citas = $query->orderBy('fecha_hora', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $citas->map(fn($c) => $this->citaService->formatearCita($c)),
            'pagination' => [
                'current_page' => $citas->currentPage(),
                'last_page' => $citas->lastPage(),
                'total' => $citas->total(),
            ],
        ]);
    }

    /**
     * Crear nueva cita (admin)
     * 
     * POST /api/admin/citas
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'required|integer|exists:clientes,id',
            'empleado_id' => 'required|integer|exists:empleados,id',
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
            'fecha_hora' => 'required|date|after_or_equal:today',
            'estado' => 'nullable|in:pendiente,confirmada,en_proceso,completada,cancelada,no_show',
            'notas' => 'nullable|string|max:500',
        ]);

        $resultado = $this->citaService->agendar($request->all(), $request->cliente_id);

        return response()->json($resultado, $resultado['success'] ? 201 : 422);
    }

    /**
     * Crear nueva cita (empleado - se asigna a sí mismo)
     * 
     * POST /api/empleado/citas
     */
    public function storeEmpleado(Request $request): JsonResponse
    {
        $user = auth()->user();
        $empleado = \App\Models\Empleado::where('user_id', $user->id)->first();

        if (!$empleado) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el empleado',
            ], 404);
        }

        $request->validate([
            'cliente_id' => 'required|integer|exists:clientes,id',
            'servicios' => 'required|array|min:1',
            'servicios.*.id' => 'required|integer|exists:servicios,id',
            'fecha_hora' => 'required|date|after_or_equal:today',
            'estado' => 'nullable|in:pendiente,confirmada',
            'notas' => 'nullable|string|max:500',
        ]);

        // Preparar datos con el empleado autenticado
        $datos = $request->all();
        $datos['empleado_id'] = $empleado->id;
        $datos['servicios'] = collect($request->servicios)->pluck('id')->toArray();

        // Cuando un empleado crea una cita, ignorar la restricción de anticipación mínima
        $resultado = $this->citaService->agendar($datos, $request->cliente_id, true);

        return response()->json($resultado, $resultado['success'] ? 201 : 422);
    }

    /**
     * Ver cita específica (admin)
     * 
     * GET /api/admin/citas/{id}
     */
    public function show(int $id): JsonResponse
    {
        $cita = Cita::with([
            'cliente', 
            'empleado.user', 
            'servicio', 
            'servicios.servicio', 
            'fotos', 
            'notificaciones',
            'calificacion'
        ])->find($id);

        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->citaService->formatearCita($cita),
        ]);
    }

    /**
     * Calendario de citas (admin)
     * 
     * GET /api/admin/citas-calendario
     */
    public function calendario(Request $request): JsonResponse
    {
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);

        $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth();
        $finMes = $inicioMes->copy()->endOfMonth();

        $citas = Cita::with(['cliente', 'empleado.user', 'servicio'])
            ->whereBetween('fecha_hora', [$inicioMes, $finMes])
            ->whereNotIn('estado', [Cita::ESTADO_CANCELADA, Cita::ESTADO_REAGENDADA])
            ->orderBy('fecha_hora')
            ->get();

        $citasPorDia = $citas->groupBy(fn($c) => $c->fecha_hora->format('Y-m-d'));

        return response()->json([
            'success' => true,
            'data' => [
                'mes' => $mes,
                'anio' => $anio,
                'citas_por_dia' => $citasPorDia->map(fn($grupo) => 
                    $grupo->map(fn($c) => [
                        'id' => $c->id,
                        'hora' => $c->fecha_hora->format('H:i'),
                        'cliente' => $c->cliente->nombre,
                        'empleado' => $c->empleado->user->nombre,
                        'servicio' => $c->servicio->nombre,
                        'estado' => $c->estado,
                    ])
                ),
            ],
        ]);
    }

    /**
     * Reagendar una cita (admin)
     * Marca la cita original como "reagendada" y crea una nueva cita
     * 
     * POST /api/admin/citas/{id}/reagendar
     */
    public function reagendarAdmin(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'fecha_hora' => 'required|string',
            'motivo' => 'nullable|string|max:500',
        ]);

        // Parsear y validar fecha
        try {
            $fechaHora = \Carbon\Carbon::parse($request->fecha_hora);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Formato de fecha inválido',
            ], 422);
        }

        // Validar que la fecha sea futura
        if ($fechaHora->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'La fecha y hora deben ser en el futuro',
            ], 422);
        }

        // Formatear correctamente para el servicio
        $fechaHoraFormateada = $fechaHora->format('Y-m-d H:i:s');

        // Admin puede reagendar cualquier cita (sin restricción de empleado)
        $resultado = $this->citaService->reagendar(
            $id,
            $fechaHoraFormateada,
            $request->motivo,
            null // Sin restricción de empleado
        );

        return response()->json($resultado, $resultado['success'] ? 200 : 422);
    }

    /**
     * Cancelar una cita (admin)
     * 
     * POST /api/admin/citas/{id}/cancelar
     */
    public function cancelarAdmin(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'motivo' => 'nullable|string|max:500',
        ]);

        $cita = Cita::find($id);

        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada',
            ], 404);
        }

        // Cambiar estado a cancelada (admin puede cancelar cualquier cita)
        $resultado = $this->citaService->cambiarEstado($id, Cita::ESTADO_CANCELADA, null);

        return response()->json($resultado, $resultado['success'] ? 200 : 422);
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Obtener cliente autenticado
     */
    private function getClienteAutenticado(Request $request): ?Cliente
    {
        $user = $request->user();
        return $user instanceof Cliente ? $user : null;
    }

    /**
     * Obtener empleado autenticado
     */
    private function getEmpleadoAutenticado(Request $request): ?\App\Models\Empleado
    {
        $user = $request->user();
        
        if ($user instanceof \App\Models\User) {
            return $user->empleado;
        }
        
        return null;
    }
}

