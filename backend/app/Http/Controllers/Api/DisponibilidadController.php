<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DisponibilidadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
}

