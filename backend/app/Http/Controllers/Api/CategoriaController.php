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

        return response()->json([
            'success' => true,
            'data' => $categorias->map(fn($c) => [
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
            ]),
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

