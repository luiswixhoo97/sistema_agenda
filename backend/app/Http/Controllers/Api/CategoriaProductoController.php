<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoriaProducto;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoriaProductoController extends Controller
{
    /**
     * Listado de categorías de productos
     * 
     * GET /api/admin/categorias-productos
     */
    public function index(Request $request): JsonResponse
    {
        $query = CategoriaProducto::withCount('productos');

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
                'productos_count' => $c->productos_count,
            ]),
        ]);
    }

    /**
     * Ver categoría de producto
     * 
     * GET /api/admin/categorias-productos/{id}
     */
    public function show(int $id): JsonResponse
    {
        $categoria = CategoriaProducto::with('productos')->find($id);

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
                'productos' => $categoria->productos->map(fn($p) => [
                    'id' => $p->id,
                    'codigo' => $p->codigo,
                    'nombre' => $p->nombre,
                    'precio' => $p->precio,
                    'inventario_actual' => $p->inventario_actual,
                    'active' => $p->active,
                ]),
            ],
        ]);
    }

    /**
     * Crear categoría de producto
     * 
     * POST /api/admin/categorias-productos
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        $categoria = CategoriaProducto::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'active' => true,
        ]);

        Auditoria::registrar('crear', 'categorias_productos', $categoria->id, null, $categoria->toArray());

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
     * Actualizar categoría de producto
     * 
     * PUT /api/admin/categorias-productos/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $categoria = CategoriaProducto::find($id);

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

        Auditoria::registrar('actualizar', 'categorias_productos', $categoria->id, $datosAnteriores, $categoria->toArray());

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
     * Eliminar categoría de producto
     * 
     * DELETE /api/admin/categorias-productos/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $categoria = CategoriaProducto::withCount('productos')->find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }

        if ($categoria->productos_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una categoría con productos',
            ], 422);
        }

        $datosAnteriores = $categoria->toArray();
        $categoria->delete();

        Auditoria::registrar('eliminar', 'categorias_productos', $id, $datosAnteriores, null);

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada correctamente',
        ]);
    }
}
