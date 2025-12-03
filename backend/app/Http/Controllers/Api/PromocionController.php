<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promocion;
use App\Models\Servicio;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'data' => $promociones->map(function($p) {
                $serviciosInfo = [];
                $tiempoTotal = 0;
                
                if (!empty($p->servicios_aplicables)) {
                    $servicios = Servicio::whereIn('id', $p->servicios_aplicables)->get();
                    foreach ($servicios as $servicio) {
                        $serviciosInfo[] = [
                            'id' => $servicio->id,
                            'nombre' => $servicio->nombre,
                            'duracion' => $servicio->duracion,
                        ];
                        $tiempoTotal += $servicio->duracion;
                    }
                }
                
                // Calcular tiempo restante en días, horas y minutos
                $ahora = now();
                $fechaFin = $p->fecha_fin->endOfDay(); // Fin del día de la fecha fin
                $diferencia = $ahora->diff($fechaFin);
                
                $dias = $diferencia->days;
                $horas = $diferencia->h;
                $minutos = $diferencia->i;
                
                return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'descripcion' => $p->descripcion,
                'descuento' => $p->descuento_porcentaje 
                    ? "{$p->descuento_porcentaje}%" 
                    : "\${$p->descuento_fijo}",
                    'descuento_porcentaje' => $p->descuento_porcentaje,
                    'descuento_fijo' => $p->descuento_fijo,
                'fecha_fin' => $p->fecha_fin->format('Y-m-d'),
                    'dias_restantes' => $dias,
                    'horas_restantes' => $horas,
                    'minutos_restantes' => $minutos,
                    'imagen' => $p->imagen ? url('storage/' . $p->imagen) : null,
                    'servicios_aplicables' => $p->servicios_aplicables,
                    'servicios_info' => $serviciosInfo,
                    'tiempo_total' => $tiempoTotal,
                ];
            }),
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
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
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

        // Manejar subida de imagen
        $rutaImagen = null;
        if ($request->hasFile('imagen')) {
            $archivo = $request->file('imagen');
            $nombreUnico = Str::uuid() . '.' . $archivo->getClientOriginalExtension();
            $rutaImagen = $archivo->storeAs('promociones', $nombreUnico, 'public');
        }

        // Procesar servicios_aplicables (puede venir como array desde FormData)
        $serviciosAplicables = null;
        if ($request->has('servicios_aplicables')) {
            $servicios = $request->input('servicios_aplicables');
            if (is_array($servicios) && count($servicios) > 0) {
                // Convertir a array de enteros
                $serviciosAplicables = array_map('intval', array_values($servicios));
            } elseif (is_string($servicios) && $servicios !== 'null' && $servicios !== '') {
                // Si viene como string JSON
                $serviciosAplicables = json_decode($servicios, true);
            }
        }

        $promocion = Promocion::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'imagen' => $rutaImagen,
            'descuento_porcentaje' => $request->descuento_porcentaje,
            'descuento_fijo' => $request->descuento_fijo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'servicios_aplicables' => $serviciosAplicables,
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
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
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

        // Procesar servicios_aplicables (puede venir como array desde FormData)
        $serviciosAplicables = null;
        if ($request->has('servicios_aplicables')) {
            $servicios = $request->input('servicios_aplicables');
            if (is_array($servicios) && count($servicios) > 0) {
                // Convertir a array de enteros
                $serviciosAplicables = array_map('intval', array_values($servicios));
            } elseif (is_string($servicios) && $servicios !== 'null' && $servicios !== '') {
                // Si viene como string JSON
                $decoded = json_decode($servicios, true);
                if (is_array($decoded)) {
                    $serviciosAplicables = array_map('intval', array_values($decoded));
                }
            }
        }

        // Manejar subida de imagen
        $datosActualizar = $request->only([
            'nombre', 'descripcion', 'descuento_porcentaje', 'descuento_fijo',
            'fecha_inicio', 'fecha_fin', 'usos_maximos', 'active'
        ]);

        // Agregar servicios_aplicables procesados
        // Si viene null o array vacío, establecer como null
        if ($request->has('servicios_aplicables')) {
            $datosActualizar['servicios_aplicables'] = (is_array($serviciosAplicables) && count($serviciosAplicables) > 0) 
                ? $serviciosAplicables 
                : null;
        }

        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            if ($promocion->imagen && Storage::disk('public')->exists($promocion->imagen)) {
                Storage::disk('public')->delete($promocion->imagen);
            }
            
            // Subir nueva imagen
            $archivo = $request->file('imagen');
            $nombreUnico = Str::uuid() . '.' . $archivo->getClientOriginalExtension();
            $rutaImagen = $archivo->storeAs('promociones', $nombreUnico, 'public');
            $datosActualizar['imagen'] = $rutaImagen;
        }

        $promocion->update($datosActualizar);

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
        $serviciosInfo = [];
        $tiempoTotal = 0;
        
        // Si tiene servicios aplicables, obtener información de cada uno
        if (!empty($promocion->servicios_aplicables)) {
            $servicios = Servicio::whereIn('id', $promocion->servicios_aplicables)->get();
            foreach ($servicios as $servicio) {
                $serviciosInfo[] = [
                    'id' => $servicio->id,
                    'nombre' => $servicio->nombre,
                    'duracion' => $servicio->duracion,
                    'precio' => $servicio->precio,
                ];
                $tiempoTotal += $servicio->duracion;
            }
        }
        
        return [
            'id' => $promocion->id,
            'nombre' => $promocion->nombre,
            'descripcion' => $promocion->descripcion,
            'imagen' => $promocion->imagen ? url('storage/' . $promocion->imagen) : null,
            'descuento_porcentaje' => $promocion->descuento_porcentaje,
            'descuento_fijo' => $promocion->descuento_fijo,
            'descuento_texto' => $promocion->descuento_porcentaje 
                ? "{$promocion->descuento_porcentaje}%" 
                : "\${$promocion->descuento_fijo}",
            'fecha_inicio' => $promocion->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $promocion->fecha_fin->format('Y-m-d'),
            'servicios_aplicables' => $promocion->servicios_aplicables,
            'servicios_info' => $serviciosInfo,
            'tiempo_total' => $tiempoTotal,
            'usos_maximos' => $promocion->usos_maximos,
            'usos_actuales' => $promocion->usos_actuales,
            'active' => $promocion->active,
            'esta_vigente' => $promocion->estaVigente(),
            'tiene_usos_disponibles' => $promocion->tieneUsosDisponibles(),
            'citas_count' => $promocion->citas_count ?? 0,
        ];
    }
}

