<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoriaController extends Controller
{
    /**
     * Listado público de categorías
     * 
     * GET /api/publico/categorias
     */
    public function indexPublico(): JsonResponse
    {
        $categorias = Categoria::with(['servicios' => function ($q) {
                $q->where('active', true)->orderBy('nombre');
            }])
            ->where('active', true)
            ->orderBy('nombre')
            ->get();

        // Agregar categoría especial "Promociones" con las promociones activas
        $promociones = \App\Models\Promocion::disponibles()
            ->orderBy('fecha_fin')
            ->get();

        $categoriasArray = $categorias->map(fn($c) => [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'descripcion' => $c->descripcion,
            'servicios_count' => $c->servicios->count(),
            'servicios' => $c->servicios->map(fn($s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'precio' => $s->precio,
                'duracion' => $s->duracion,
            ]),
        ])->toArray();

        // Agregar categoría "Promociones" si hay promociones disponibles
        if ($promociones->count() > 0) {
            $promocionesArray = $promociones->map(function($p) {
                $serviciosInfo = [];
                $tiempoTotal = 0;
                $precioTotal = 0;
                
                // Si tiene servicios específicos, obtenerlos
                if (!empty($p->servicios_aplicables) && is_array($p->servicios_aplicables) && count($p->servicios_aplicables) > 0) {
                    $servicios = \App\Models\Servicio::whereIn('id', $p->servicios_aplicables)->get();
                    foreach ($servicios as $servicio) {
                        $serviciosInfo[] = [
                            'id' => $servicio->id,
                            'nombre' => $servicio->nombre,
                            'precio' => $servicio->precio,
                            'duracion' => $servicio->duracion,
                        ];
                        $tiempoTotal += $servicio->duracion;
                        $precioTotal += $servicio->precio;
                    }
                } else {
                    // Si no tiene servicios específicos, aplicar a todos los servicios
                    // Para la categoría, no calculamos precio total (se calculará al seleccionar servicios)
                    $tiempoTotal = 0;
                    $precioTotal = 0;
                }
                
                // Calcular precio con descuento (solo si hay servicios específicos)
                $precioConDescuento = $precioTotal;
                if ($precioTotal > 0) {
                    if ($p->descuento_porcentaje) {
                        $precioConDescuento = $precioTotal * (1 - $p->descuento_porcentaje / 100);
                    } elseif ($p->descuento_fijo) {
                        $precioConDescuento = max(0, $precioTotal - $p->descuento_fijo);
                    }
                }
                
                return [
                    'id' => 'promo_' . $p->id, // ID especial para promociones
                    'nombre' => $p->nombre,
                    'descripcion' => $p->descripcion,
                    'precio' => $precioTotal,
                    'precio_con_descuento' => $precioConDescuento,
                    'duracion' => $tiempoTotal,
                    'es_promocion' => true,
                    'promocion_id' => $p->id,
                    'descuento' => $p->descuento_porcentaje 
                        ? "{$p->descuento_porcentaje}%" 
                        : "\${$p->descuento_fijo}",
                    'servicios_incluidos' => $serviciosInfo,
                    'aplica_a_todos' => empty($p->servicios_aplicables) || count($p->servicios_aplicables) === 0,
                ];
            })->toArray();
            
            $categoriasArray[] = [
                'id' => 999999, // ID especial para categoría Promociones (número alto para evitar conflictos)
                'nombre' => 'Promociones',
                'descripcion' => 'Ofertas especiales y paquetes promocionales',
                'servicios_count' => count($promocionesArray),
                'servicios' => $promocionesArray,
                'es_promociones' => true,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $categoriasArray,
        ]);
    }

    /**
     * Listado de categorías (admin)
     * 
     * GET /api/admin/categorias
     */
    public function index(Request $request): JsonResponse
    {
        $query = Categoria::withCount('servicios');

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $categorias = $query->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $categorias->map(fn($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'descripcion' => $c->descripcion,
                'active' => $c->active,
                'servicios_count' => $c->servicios_count,
            ]),
        ]);
    }

    /**
     * Ver categoría
     * 
     * GET /api/admin/categorias/{id}
     */
    public function show(int $id): JsonResponse
    {
        $categoria = Categoria::with('servicios')->find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'descripcion' => $categoria->descripcion,
                'active' => $categoria->active,
                'servicios' => $categoria->servicios->map(fn($s) => [
                    'id' => $s->id,
                    'nombre' => $s->nombre,
                    'precio' => $s->precio,
                    'duracion' => $s->duracion,
                    'active' => $s->active,
                ]),
            ],
        ]);
    }

    /**
     * Crear categoría
     * 
     * POST /api/admin/categorias
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        $categoria = Categoria::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'active' => true,
        ]);

        Auditoria::registrar('crear', 'categorias', $categoria->id, null, $categoria->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Categoría creada correctamente',
            'data' => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'descripcion' => $categoria->descripcion,
                'active' => $categoria->active,
            ],
        ], 201);
    }

    /**
     * Actualizar categoría
     * 
     * PUT /api/admin/categorias/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ]);

        $datosAnteriores = $categoria->toArray();
        
        $categoria->update($request->only(['nombre', 'descripcion', 'active']));

        Auditoria::registrar('actualizar', 'categorias', $categoria->id, $datosAnteriores, $categoria->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Categoría actualizada correctamente',
            'data' => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'descripcion' => $categoria->descripcion,
                'active' => $categoria->active,
            ],
        ]);
    }

    /**
     * Eliminar categoría
     * 
     * DELETE /api/admin/categorias/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $categoria = Categoria::withCount('servicios')->find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        // No permitir eliminar si tiene servicios
        if ($categoria->servicios_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una categoría con servicios',
            ], 422);
        }

        $datosAnteriores = $categoria->toArray();
        $categoria->delete();

        Auditoria::registrar('eliminar', 'categorias', $id, $datosAnteriores, null);

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente',
        ]);
    }
}

