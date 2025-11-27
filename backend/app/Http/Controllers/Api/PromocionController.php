<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promocion;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PromocionController extends Controller
{
    /**
     * Listado público de promociones vigentes
     * 
     * GET /api/publico/promociones
     */
    public function indexPublico(): JsonResponse
    {
        $promociones = Promocion::disponibles()
            ->orderBy('fecha_fin')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $promociones->map(fn($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'descripcion' => $p->descripcion,
                'descuento' => $p->descuento_porcentaje 
                    ? "{$p->descuento_porcentaje}%" 
                    : "\${$p->descuento_fijo}",
                'fecha_fin' => $p->fecha_fin->format('Y-m-d'),
                'dias_restantes' => now()->diffInDays($p->fecha_fin),
            ]),
        ]);
    }

    /**
     * Listado de promociones (admin)
     * 
     * GET /api/admin/promociones
     */
    public function index(Request $request): JsonResponse
    {
        $query = Promocion::query();

        // Filtros
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }
        if ($request->has('vigentes')) {
            $request->boolean('vigentes') ? $query->vigentes() : $query->where('fecha_fin', '<', now());
        }

        $promociones = $query->orderBy('fecha_inicio', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $promociones->map(fn($p) => $this->formatearPromocion($p)),
        ]);
    }

    /**
     * Ver promoción
     * 
     * GET /api/admin/promociones/{id}
     */
    public function show(int $id): JsonResponse
    {
        $promocion = Promocion::withCount('citas')->find($id);

        if (!$promocion) {
            return response()->json([
                'success' => false,
                'message' => 'Promoción no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatearPromocion($promocion),
        ]);
    }

    /**
     * Crear promoción
     * 
     * POST /api/admin/promociones
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'descuento_porcentaje' => 'nullable|integer|min:1|max:100',
            'descuento_fijo' => 'nullable|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'servicios_aplicables' => 'nullable|array',
            'servicios_aplicables.*' => 'integer|exists:servicios,id',
            'usos_maximos' => 'nullable|integer|min:1',
        ]);

        // Validar que tenga al menos un tipo de descuento
        if (!$request->descuento_porcentaje && !$request->descuento_fijo) {
            return response()->json([
                'success' => false,
                'message' => 'Debe especificar un descuento (porcentaje o fijo)',
            ], 422);
        }

        $promocion = Promocion::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'descuento_porcentaje' => $request->descuento_porcentaje,
            'descuento_fijo' => $request->descuento_fijo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'servicios_aplicables' => $request->servicios_aplicables,
            'usos_maximos' => $request->usos_maximos,
            'usos_actuales' => 0,
            'active' => true,
        ]);

        Auditoria::registrar('crear', 'promociones', $promocion->id, null, $promocion->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Promoción creada correctamente',
            'data' => $this->formatearPromocion($promocion),
        ], 201);
    }

    /**
     * Actualizar promoción
     * 
     * PUT /api/admin/promociones/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $promocion = Promocion::find($id);

        if (!$promocion) {
            return response()->json([
                'success' => false,
                'message' => 'Promoción no encontrada',
            ], 404);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string',
            'descuento_porcentaje' => 'nullable|integer|min:1|max:100',
            'descuento_fijo' => 'nullable|numeric|min:0',
            'fecha_inicio' => 'sometimes|date',
            'fecha_fin' => 'sometimes|date|after_or_equal:fecha_inicio',
            'servicios_aplicables' => 'nullable|array',
            'servicios_aplicables.*' => 'integer|exists:servicios,id',
            'usos_maximos' => 'nullable|integer|min:1',
            'active' => 'sometimes|boolean',
        ]);

        $datosAnteriores = $promocion->toArray();

        $promocion->update($request->only([
            'nombre', 'descripcion', 'descuento_porcentaje', 'descuento_fijo',
            'fecha_inicio', 'fecha_fin', 'servicios_aplicables', 'usos_maximos', 'active'
        ]));

        Auditoria::registrar('actualizar', 'promociones', $promocion->id, $datosAnteriores, $promocion->fresh()->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Promoción actualizada correctamente',
            'data' => $this->formatearPromocion($promocion->fresh()),
        ]);
    }

    /**
     * Eliminar promoción
     * 
     * DELETE /api/admin/promociones/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $promocion = Promocion::find($id);

        if (!$promocion) {
            return response()->json([
                'success' => false,
                'message' => 'Promoción no encontrada',
            ], 404);
        }

        $datosAnteriores = $promocion->toArray();
        $promocion->delete();

        Auditoria::registrar('eliminar', 'promociones', $id, $datosAnteriores, null);

        return response()->json([
            'success' => true,
            'message' => 'Promoción eliminada correctamente',
        ]);
    }

    /**
     * Formatear promoción para respuesta
     */
    private function formatearPromocion(Promocion $promocion): array
    {
        return [
            'id' => $promocion->id,
            'nombre' => $promocion->nombre,
            'descripcion' => $promocion->descripcion,
            'descuento_porcentaje' => $promocion->descuento_porcentaje,
            'descuento_fijo' => $promocion->descuento_fijo,
            'descuento_texto' => $promocion->descuento_porcentaje 
                ? "{$promocion->descuento_porcentaje}%" 
                : "\${$promocion->descuento_fijo}",
            'fecha_inicio' => $promocion->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $promocion->fecha_fin->format('Y-m-d'),
            'servicios_aplicables' => $promocion->servicios_aplicables,
            'usos_maximos' => $promocion->usos_maximos,
            'usos_actuales' => $promocion->usos_actuales,
            'active' => $promocion->active,
            'esta_vigente' => $promocion->estaVigente(),
            'tiene_usos_disponibles' => $promocion->tieneUsosDisponibles(),
            'citas_count' => $promocion->citas_count ?? 0,
        ];
    }
}

