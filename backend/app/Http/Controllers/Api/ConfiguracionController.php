<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Empleado;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConfiguracionController extends Controller
{
    /**
     * Dashboard principal
     * 
     * GET /api/admin/dashboard
     */
    public function dashboard(): JsonResponse
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        // Citas del día
        $citasHoy = Cita::whereDate('fecha_hora', $hoy)
            ->whereNotIn('estado', ['cancelada'])
            ->count();

        // Citas confirmadas (pendientes de finalizar)
        $citasConfirmadas = Cita::where('estado', 'confirmada')
            ->where('fecha_hora', '>=', now())
            ->count();

        // Ingresos del día
        $ingresosHoy = Cita::whereDate('fecha_hora', $hoy)
            ->where('estado', 'completada')
            ->sum('precio_final');

        // Ingresos del mes
        $ingresosMes = Cita::whereBetween('fecha_hora', [$inicioMes, $finMes])
            ->where('estado', 'completada')
            ->sum('precio_final');

        // Servicios más populares
        $serviciosPopulares = DB::table('citas_servicios')
            ->join('servicios', 'citas_servicios.servicio_id', '=', 'servicios.id')
            ->join('citas', 'citas_servicios.cita_id', '=', 'citas.id')
            ->whereBetween('citas.fecha_hora', [$inicioMes, $finMes])
            ->where('citas.estado', 'completada')
            ->select('servicios.nombre', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('servicios.id', 'servicios.nombre')
            ->orderByDesc('cantidad')
            ->limit(5)
            ->get();

        // Empleados más ocupados
        $empleadosOcupados = Cita::with('empleado.user')
            ->whereBetween('fecha_hora', [$inicioMes, $finMes])
            ->whereNotIn('estado', ['cancelada'])
            ->select('empleado_id', DB::raw('COUNT(*) as citas'))
            ->groupBy('empleado_id')
            ->orderByDesc('citas')
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'nombre' => $c->empleado->user->nombre ?? 'N/A',
                'citas' => $c->citas,
            ]);

        // Estadísticas generales
        $totalClientes = Cliente::where('active', true)->count();
        $totalEmpleados = Empleado::where('active', true)->count();
        $totalServicios = Servicio::where('active', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'citas_hoy' => $citasHoy,
                'citas_confirmadas' => $citasConfirmadas,
                'ingresos_hoy' => $ingresosHoy,
                'ingresos_mes' => $ingresosMes,
                'servicios_populares' => $serviciosPopulares,
                'empleados_ocupados' => $empleadosOcupados,
                'estadisticas' => [
                    'total_clientes' => $totalClientes,
                    'total_empleados' => $totalEmpleados,
                    'total_servicios' => $totalServicios,
                ],
            ],
        ]);
    }

    /**
     * Obtener configuraciones
     * 
     * GET /api/admin/configuracion
     */
    public function index(): JsonResponse
    {
        $configuraciones = Configuracion::all();

        return response()->json([
            'success' => true,
            'data' => $configuraciones->map(fn($c) => [
                'clave' => $c->clave,
                'valor' => Configuracion::get($c->clave),
                'tipo' => $c->tipo,
                'descripcion' => $c->descripcion,
            ]),
        ]);
    }

    /**
     * Actualizar configuraciones
     * 
     * PUT /api/admin/configuracion
     */
    public function actualizar(Request $request): JsonResponse
    {
        $request->validate([
            'configuraciones' => 'required|array',
            'configuraciones.*.clave' => 'required|string',
            'configuraciones.*.valor' => 'required',
        ]);

        foreach ($request->configuraciones as $config) {
            $anterior = Configuracion::where('clave', $config['clave'])->first();
            $valorAnterior = $anterior ? $anterior->valor : null;

            Configuracion::set($config['clave'], $config['valor']);

            if ($valorAnterior !== $config['valor']) {
                Auditoria::registrar(
                    'actualizar',
                    'configuracion',
                    0,
                    ['clave' => $config['clave'], 'valor' => $valorAnterior],
                    ['clave' => $config['clave'], 'valor' => $config['valor']]
                );
            }
        }

        // Limpiar caché de configuraciones
        Configuracion::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada correctamente',
        ]);
    }

    /**
     * Reporte de citas
     * 
     * GET /api/admin/reportes/citas
     */
    public function reporteCitas(Request $request): JsonResponse
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $citas = Cita::with(['cliente', 'empleado.user', 'servicio'])
            ->whereBetween('fecha_hora', [$request->desde, $request->hasta . ' 23:59:59'])
            ->get();

        $resumen = [
            'total' => $citas->count(),
            'completadas' => $citas->where('estado', 'completada')->count(),
            'confirmadas' => $citas->where('estado', 'confirmada')->count(),
            'reagendadas' => $citas->where('estado', 'reagendada')->count(),
            'canceladas' => $citas->where('estado', 'cancelada')->count(),
            'ingresos' => $citas->where('estado', 'completada')->sum('precio_final'),
        ];

        $porEstado = $citas->groupBy('estado')
            ->map(fn($grupo) => $grupo->count());

        $porDia = $citas->groupBy(fn($c) => $c->fecha_hora->format('Y-m-d'))
            ->map(fn($grupo) => [
                'total' => $grupo->count(),
                'ingresos' => $grupo->where('estado', 'completada')->sum('precio_final'),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'resumen' => $resumen,
                'por_estado' => $porEstado,
                'por_dia' => $porDia,
            ],
        ]);
    }

    /**
     * Reporte de ingresos
     * 
     * GET /api/admin/reportes/ingresos
     */
    public function reporteIngresos(Request $request): JsonResponse
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $citas = Cita::where('estado', 'completada')
            ->whereBetween('fecha_hora', [$request->desde, $request->hasta . ' 23:59:59'])
            ->get();

        $porDia = $citas->groupBy(fn($c) => $c->fecha_hora->format('Y-m-d'))
            ->map(fn($grupo) => $grupo->sum('precio_final'));

        $porMetodoPago = $citas->groupBy('metodo_pago')
            ->map(fn($grupo) => $grupo->sum('precio_final'));

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $citas->sum('precio_final'),
                'cantidad_citas' => $citas->count(),
                'promedio_cita' => $citas->count() > 0 ? round($citas->sum('precio_final') / $citas->count(), 2) : 0,
                'por_dia' => $porDia,
                'por_metodo_pago' => $porMetodoPago,
            ],
        ]);
    }

    /**
     * Reporte de empleados
     * 
     * GET /api/admin/reportes/empleados
     */
    public function reporteEmpleados(Request $request): JsonResponse
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $empleados = Empleado::with('user')
            ->where('active', true)
            ->get()
            ->map(function ($empleado) use ($request) {
                $citas = Cita::where('empleado_id', $empleado->id)
                    ->whereBetween('fecha_hora', [$request->desde, $request->hasta . ' 23:59:59'])
                    ->get();

                return [
                    'id' => $empleado->id,
                    'nombre' => $empleado->user->nombre,
                    'citas_total' => $citas->count(),
                    'citas_completadas' => $citas->where('estado', 'completada')->count(),
                    'ingresos' => $citas->where('estado', 'completada')->sum('precio_final'),
                    'promedio_calificacion' => round($empleado->promedio_calificacion, 1),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $empleados,
        ]);
    }

    /**
     * Reporte de servicios
     * 
     * GET /api/admin/reportes/servicios
     */
    public function reporteServicios(Request $request): JsonResponse
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $servicios = DB::table('citas_servicios')
            ->join('servicios', 'citas_servicios.servicio_id', '=', 'servicios.id')
            ->join('citas', 'citas_servicios.cita_id', '=', 'citas.id')
            ->whereBetween('citas.fecha_hora', [$request->desde, $request->hasta . ' 23:59:59'])
            ->where('citas.estado', 'completada')
            ->select(
                'servicios.id',
                'servicios.nombre',
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(citas_servicios.precio_aplicado) as ingresos')
            )
            ->groupBy('servicios.id', 'servicios.nombre')
            ->orderByDesc('cantidad')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $servicios,
        ]);
    }

    /**
     * Auditoría
     * 
     * GET /api/admin/auditoria
     */
    public function auditoria(Request $request): JsonResponse
    {
        $query = Auditoria::with('usuario');

        if ($request->has('tabla')) {
            $query->where('tabla', $request->tabla);
        }
        if ($request->has('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }
        if ($request->has('desde')) {
            $query->where('created_at', '>=', $request->desde);
        }

        $auditorias = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $auditorias->map(fn($a) => [
                'id' => $a->id,
                'usuario' => $a->usuario?->nombre ?? 'Sistema',
                'accion' => $a->accion,
                'tabla' => $a->tabla,
                'registro_id' => $a->registro_id,
                'ip_address' => $a->ip_address,
                'created_at' => $a->created_at?->format('Y-m-d H:i:s'),
            ]),
            'pagination' => [
                'current_page' => $auditorias->currentPage(),
                'last_page' => $auditorias->lastPage(),
                'total' => $auditorias->total(),
            ],
        ]);
    }
}

