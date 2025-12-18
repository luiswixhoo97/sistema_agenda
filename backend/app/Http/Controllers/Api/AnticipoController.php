<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReglaAnticipo;
use App\Models\ReglaAnticipoFecha;
use App\Models\ReglaAnticipoMonto;
use App\Models\ReglaAnticipoServicio;
use App\Models\Venta;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnticipoController extends Controller
{
    /**
     * Listar reglas de anticipo
     * 
     * GET /api/admin/anticipos/reglas
     */
    public function index(Request $request): JsonResponse
    {
        $query = ReglaAnticipo::query();

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }
        if ($request->has('tipo_regla')) {
            $query->where('tipo_regla', $request->tipo_regla);
        }

        $reglas = $query->ordenadasPorPrioridad()->get();

        return response()->json([
            'success' => true,
            'data' => $reglas->map(fn($r) => $this->formatearRegla($r)),
        ]);
    }

    /**
     * Ver regla de anticipo completa
     * 
     * GET /api/admin/anticipos/reglas/{id}
     */
    public function show(int $id): JsonResponse
    {
        $regla = ReglaAnticipo::with([
            'reglaFecha',
            'reglaMonto',
            'reglasServicio.servicio'
        ])->find($id);

        if (!$regla) {
            return response()->json([
                'success' => false,
                'message' => 'Regla de anticipo no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatearReglaCompleta($regla),
        ]);
    }

    /**
     * Crear regla de anticipo
     * 
     * POST /api/admin/anticipos/reglas
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'tipo_regla' => 'required|in:fecha,monto,servicio',
            'tipo_calculo' => 'required|in:porcentaje,monto_fijo',
            'valor_calculo' => 'required|numeric|min:0',
            'prioridad' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
            // Configuración específica según tipo
            'fecha_inicio' => 'required_if:tipo_regla,fecha|date',
            'fecha_fin' => 'required_if:tipo_regla,fecha|date|after_or_equal:fecha_inicio',
            'aplica_todos_dias' => 'nullable|boolean',
            'dias_semana' => 'nullable|array',
            'monto_minimo' => 'required_if:tipo_regla,monto|numeric|min:0',
            'monto_maximo' => 'nullable|numeric|min:0|gte:monto_minimo',
            'servicios' => 'required_if:tipo_regla,servicio|array',
            'servicios.*' => 'exists:servicios,id',
        ]);

        DB::beginTransaction();
        try {
            $regla = ReglaAnticipo::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'tipo_regla' => $request->tipo_regla,
                'tipo_calculo' => $request->tipo_calculo,
                'valor_calculo' => $request->valor_calculo,
                'prioridad' => $request->prioridad ?? 0,
                'activo' => $request->boolean('activo', true),
            ]);

            // Crear configuración específica según tipo
            if ($request->tipo_regla === 'fecha') {
                ReglaAnticipoFecha::create([
                    'regla_anticipo_id' => $regla->id,
                    'fecha_inicio' => $request->fecha_inicio,
                    'fecha_fin' => $request->fecha_fin,
                    'aplica_todos_dias' => $request->boolean('aplica_todos_dias', true),
                    'dias_semana' => $request->aplica_todos_dias ? null : $request->dias_semana,
                ]);
            } elseif ($request->tipo_regla === 'monto') {
                ReglaAnticipoMonto::create([
                    'regla_anticipo_id' => $regla->id,
                    'monto_minimo' => $request->monto_minimo,
                    'monto_maximo' => $request->monto_maximo,
                ]);
            } elseif ($request->tipo_regla === 'servicio') {
                foreach ($request->servicios as $servicioId) {
                    ReglaAnticipoServicio::create([
                        'regla_anticipo_id' => $regla->id,
                        'servicio_id' => $servicioId,
                    ]);
                }
            }

            Auditoria::registrar('crear', 'reglas_anticipo', $regla->id, null, $regla->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Regla de anticipo creada correctamente',
                'data' => $this->formatearReglaCompleta($regla->load(['reglaFecha', 'reglaMonto', 'reglasServicio.servicio'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear regla: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar regla de anticipo
     * 
     * PUT /api/admin/anticipos/reglas/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $regla = ReglaAnticipo::with(['reglaFecha', 'reglaMonto', 'reglasServicio'])->find($id);

        if (!$regla) {
            return response()->json([
                'success' => false,
                'message' => 'Regla de anticipo no encontrada',
            ], 404);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string',
            'tipo_calculo' => 'sometimes|in:porcentaje,monto_fijo',
            'valor_calculo' => 'sometimes|numeric|min:0',
            'prioridad' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $datosAnteriores = $regla->toArray();
            
            $regla->update($request->only([
                'nombre', 'descripcion', 'tipo_calculo', 'valor_calculo', 'prioridad', 'activo'
            ]));

            Auditoria::registrar('actualizar', 'reglas_anticipo', $regla->id, $datosAnteriores, $regla->toArray());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Regla de anticipo actualizada correctamente',
                'data' => $this->formatearReglaCompleta($regla->load(['reglaFecha', 'reglaMonto', 'reglasServicio.servicio'])),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar regla: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar regla de anticipo
     * 
     * DELETE /api/admin/anticipos/reglas/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $regla = ReglaAnticipo::find($id);

        if (!$regla) {
            return response()->json([
                'success' => false,
                'message' => 'Regla de anticipo no encontrada',
            ], 404);
        }

        $datosAnteriores = $regla->toArray();
        $regla->delete(); // Las relaciones se eliminan en cascade

        Auditoria::registrar('eliminar', 'reglas_anticipo', $id, $datosAnteriores, null);

        return response()->json([
            'success' => true,
            'message' => 'Regla de anticipo eliminada correctamente',
        ]);
    }

    /**
     * Calcular anticipo requerido para una venta
     * 
     * POST /api/admin/anticipos/calcular
     */
    public function calcular(Request $request): JsonResponse
    {
        $request->validate([
            'total' => 'required|numeric|min:0',
            'fecha_venta' => 'nullable|date',
            'servicios' => 'nullable|array',
            'servicios.*' => 'exists:servicios,id',
        ]);

        $total = $request->total;
        $fechaVenta = $request->fecha_venta ? \Carbon\Carbon::parse($request->fecha_venta) : now();
        $serviciosIds = $request->servicios ?? [];

        // Obtener reglas activas ordenadas por prioridad
        $reglas = ReglaAnticipo::activas()
            ->ordenadasPorPrioridad()
            ->with(['reglaFecha', 'reglaMonto', 'reglasServicio'])
            ->get();

        $reglasAplicables = [];
        $mayorAnticipo = 0;
        $reglaAplicada = null;

        foreach ($reglas as $regla) {
            $aplica = false;

            // Evaluar según tipo de regla
            if ($regla->tipo_regla === 'fecha' && $regla->reglaFecha) {
                $aplica = $regla->reglaFecha->aplicaEnFecha($fechaVenta);
            } elseif ($regla->tipo_regla === 'monto' && $regla->reglaMonto) {
                $aplica = $regla->reglaMonto->aplicaAMonto($total);
            } elseif ($regla->tipo_regla === 'servicio') {
                $serviciosRegla = $regla->reglasServicio->pluck('servicio_id')->toArray();
                $aplica = !empty(array_intersect($serviciosIds, $serviciosRegla));
            }

            if ($aplica) {
                // Calcular anticipo según tipo de cálculo
                $anticipo = 0;
                if ($regla->tipo_calculo === 'porcentaje') {
                    $anticipo = $total * ($regla->valor_calculo / 100);
                } else {
                    $anticipo = $regla->valor_calculo;
                }

                $reglasAplicables[] = [
                    'regla_id' => $regla->id,
                    'nombre' => $regla->nombre,
                    'anticipo' => $anticipo,
                ];

                // Seleccionar la regla que requiera mayor anticipo
                if ($anticipo > $mayorAnticipo) {
                    $mayorAnticipo = $anticipo;
                    $reglaAplicada = $regla;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'requiere_anticipo' => $mayorAnticipo > 0,
                'monto_anticipo' => $mayorAnticipo,
                'regla_aplicada' => $reglaAplicada ? [
                    'id' => $reglaAplicada->id,
                    'nombre' => $reglaAplicada->nombre,
                ] : null,
                'reglas_evaluadas' => $reglasAplicables,
            ],
        ]);
    }

    /**
     * Validar anticipo requerido (endpoint público)
     * 
     * POST /api/publico/anticipo/validar
     */
    public function validarPublico(Request $request): JsonResponse
    {
        $request->validate([
            'servicios' => 'required|array|min:1',
            'servicios.*' => 'integer|exists:servicios,id',
            'total' => 'required|numeric|min:0',
            'fecha_cita' => 'required|date',
        ]);

        $total = $request->total;
        $fechaCita = \Carbon\Carbon::parse($request->fecha_cita);
        $serviciosIds = $request->servicios;

        // Obtener reglas activas ordenadas por prioridad
        $reglas = ReglaAnticipo::activas()
            ->ordenadasPorPrioridad()
            ->with(['reglaFecha', 'reglaMonto', 'reglasServicio'])
            ->get();

        $reglasAplicables = [];
        $mayorAnticipo = 0;
        $reglaAplicada = null;

        foreach ($reglas as $regla) {
            $aplica = false;

            // Evaluar según tipo de regla
            if ($regla->tipo_regla === 'fecha' && $regla->reglaFecha) {
                $aplica = $regla->reglaFecha->aplicaEnFecha($fechaCita);
            } elseif ($regla->tipo_regla === 'monto' && $regla->reglaMonto) {
                $aplica = $regla->reglaMonto->aplicaAMonto($total);
            } elseif ($regla->tipo_regla === 'servicio') {
                $serviciosRegla = $regla->reglasServicio->pluck('servicio_id')->toArray();
                $aplica = !empty(array_intersect($serviciosIds, $serviciosRegla));
            }

            if ($aplica) {
                // Calcular anticipo según tipo de cálculo
                $anticipo = 0;
                if ($regla->tipo_calculo === 'porcentaje') {
                    $anticipo = $total * ($regla->valor_calculo / 100);
                } else {
                    $anticipo = $regla->valor_calculo;
                }

                $reglasAplicables[] = [
                    'regla_id' => $regla->id,
                    'nombre' => $regla->nombre,
                    'anticipo' => $anticipo,
                ];

                // Seleccionar la regla que requiera mayor anticipo
                if ($anticipo > $mayorAnticipo) {
                    $mayorAnticipo = $anticipo;
                    $reglaAplicada = $regla;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'requiere_anticipo' => $mayorAnticipo > 0,
                'monto_anticipo' => round($mayorAnticipo, 2),
                'regla_aplicada' => $reglaAplicada ? [
                    'id' => $reglaAplicada->id,
                    'nombre' => $reglaAplicada->nombre,
                ] : null,
            ],
        ]);
    }

    /**
     * Evaluar qué reglas aplican a una venta
     * 
     * POST /api/admin/anticipos/evaluar
     */
    public function evaluar(Request $request): JsonResponse
    {
        $request->validate([
            'total' => 'required|numeric|min:0',
            'fecha_venta' => 'nullable|date',
            'servicios' => 'nullable|array',
            'servicios.*' => 'exists:servicios,id',
        ]);

        $total = $request->total;
        $fechaVenta = $request->fecha_venta ? \Carbon\Carbon::parse($request->fecha_venta) : now();
        $serviciosIds = $request->servicios ?? [];

        $reglas = ReglaAnticipo::activas()
            ->ordenadasPorPrioridad()
            ->with(['reglaFecha', 'reglaMonto', 'reglasServicio'])
            ->get();

        $resultado = [];

        foreach ($reglas as $regla) {
            $aplica = false;
            $razon = '';

            if ($regla->tipo_regla === 'fecha' && $regla->reglaFecha) {
                $aplica = $regla->reglaFecha->aplicaEnFecha($fechaVenta);
                $razon = $aplica ? 'Fecha dentro del rango configurado' : 'Fecha fuera del rango';
            } elseif ($regla->tipo_regla === 'monto' && $regla->reglaMonto) {
                $aplica = $regla->reglaMonto->aplicaAMonto($total);
                $razon = $aplica ? 'Monto dentro del rango configurado' : 'Monto fuera del rango';
            } elseif ($regla->tipo_regla === 'servicio') {
                $serviciosRegla = $regla->reglasServicio->pluck('servicio_id')->toArray();
                $aplica = !empty(array_intersect($serviciosIds, $serviciosRegla));
                $razon = $aplica ? 'Servicios coinciden con la regla' : 'Servicios no coinciden';
            }

            $resultado[] = [
                'regla_id' => $regla->id,
                'nombre' => $regla->nombre,
                'tipo_regla' => $regla->tipo_regla,
                'aplica' => $aplica,
                'razon' => $razon,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ]);
    }

    /**
     * Formatear regla básica
     */
    private function formatearRegla(ReglaAnticipo $regla): array
    {
        return [
            'id' => $regla->id,
            'nombre' => $regla->nombre,
            'descripcion' => $regla->descripcion,
            'tipo_regla' => $regla->tipo_regla,
            'tipo_calculo' => $regla->tipo_calculo,
            'valor_calculo' => $regla->valor_calculo,
            'prioridad' => $regla->prioridad,
            'activo' => $regla->activo,
        ];
    }

    /**
     * Formatear regla completa con configuración
     */
    private function formatearReglaCompleta(ReglaAnticipo $regla): array
    {
        $data = $this->formatearRegla($regla);

        if ($regla->tipo_regla === 'fecha' && $regla->reglaFecha) {
            $data['configuracion_fecha'] = [
                'fecha_inicio' => $regla->reglaFecha->fecha_inicio,
                'fecha_fin' => $regla->reglaFecha->fecha_fin,
                'aplica_todos_dias' => $regla->reglaFecha->aplica_todos_dias,
                'dias_semana' => $regla->reglaFecha->dias_semana,
            ];
        } elseif ($regla->tipo_regla === 'monto' && $regla->reglaMonto) {
            $data['configuracion_monto'] = [
                'monto_minimo' => $regla->reglaMonto->monto_minimo,
                'monto_maximo' => $regla->reglaMonto->monto_maximo,
            ];
        } elseif ($regla->tipo_regla === 'servicio') {
            $data['configuracion_servicios'] = $regla->reglasServicio->map(fn($rs) => [
                'servicio_id' => $rs->servicio_id,
                'servicio' => $rs->servicio ? [
                    'id' => $rs->servicio->id,
                    'nombre' => $rs->servicio->nombre,
                ] : null,
            ]);
        }

        return $data;
    }
}
