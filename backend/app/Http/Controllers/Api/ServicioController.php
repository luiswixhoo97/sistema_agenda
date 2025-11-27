<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use App\Models\Categoria;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServicioController extends Controller
{
    /**
     * Listado público de servicios (sin autenticación)
     * 
     * GET /api/publico/servicios
     */
    public function indexPublico(Request $request): JsonResponse
    {
        $query = Servicio::with('categoria')
            ->where('active', true)
            ->whereHas('categoria', fn($q) => $q->where('active', true));

        // Filtrar por categoría
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        // Búsqueda
        if ($request->has('buscar')) {
            $query->where('nombre', 'like', "%{$request->buscar}%");
        }

        $servicios = $query->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $servicios->map(fn($s) => [
                'id' => $s->id,
                'nombre' => $s->nombre,
                'descripcion' => $s->descripcion,
                'precio' => $s->precio,
                'precio_texto' => '$' . number_format($s->precio, 2),
                'duracion' => $s->duracion,
                'duracion_texto' => $this->formatearDuracion($s->duracion),
                'categoria' => [
                    'id' => $s->categoria->id,
                    'nombre' => $s->categoria->nombre,
                ],
            ]),
        ]);
    }

    /**
     * Listado de servicios (admin)
     * 
     * GET /api/admin/servicios
     */
    public function index(Request $request): JsonResponse
    {
        $query = Servicio::with('categoria');

        // Filtros
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }
        if ($request->has('buscar')) {
            $query->where('nombre', 'like', "%{$request->buscar}%");
        }

        $servicios = $query->orderBy('categoria_id')
            ->orderBy('nombre')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $servicios->map(fn($s) => $this->formatearServicio($s)),
            'pagination' => [
                'current_page' => $servicios->currentPage(),
                'last_page' => $servicios->lastPage(),
                'total' => $servicios->total(),
            ],
        ]);
    }

    /**
     * Ver servicio
     * 
     * GET /api/admin/servicios/{id}
     */
    public function show(int $id): JsonResponse
    {
        $servicio = Servicio::with(['categoria', 'empleados.user'])->find($id);

        if (!$servicio) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado',
            ], 404);
        }

        $data = $this->formatearServicio($servicio);
        $data['empleados'] = $servicio->empleados->map(fn($e) => [
            'id' => $e->id,
            'nombre' => $e->user->nombre,
            'precio_especial' => $e->pivot->precio_especial,
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Crear servicio
     * 
     * POST /api/admin/servicios
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'duracion' => 'required|integer|min:5|max:480',
        ]);

        $servicio = Servicio::create([
            'categoria_id' => $request->categoria_id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'precio' => $request->precio,
            'duracion' => $request->duracion,
            'active' => true,
        ]);

        Auditoria::registrar('crear', 'servicios', $servicio->id, null, $servicio->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Servicio creado correctamente',
            'data' => $this->formatearServicio($servicio->load('categoria')),
        ], 201);
    }

    /**
     * Actualizar servicio
     * 
     * PUT /api/admin/servicios/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $servicio = Servicio::find($id);

        if (!$servicio) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado',
            ], 404);
        }

        $request->validate([
            'categoria_id' => 'sometimes|exists:categorias,id',
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string',
            'precio' => 'sometimes|numeric|min:0',
            'duracion' => 'sometimes|integer|min:5|max:480',
            'active' => 'sometimes|boolean',
        ]);

        $datosAnteriores = $servicio->toArray();
        
        $servicio->update($request->only([
            'categoria_id', 'nombre', 'descripcion', 'precio', 'duracion', 'active'
        ]));

        Auditoria::registrar('actualizar', 'servicios', $servicio->id, $datosAnteriores, $servicio->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Servicio actualizado correctamente',
            'data' => $this->formatearServicio($servicio->load('categoria')),
        ]);
    }

    /**
     * Eliminar servicio (soft delete)
     * 
     * DELETE /api/admin/servicios/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $servicio = Servicio::find($id);

        if (!$servicio) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado',
            ], 404);
        }

        $datosAnteriores = $servicio->toArray();
        $servicio->delete();

        Auditoria::registrar('eliminar', 'servicios', $id, $datosAnteriores, null);

        return response()->json([
            'success' => true,
            'message' => 'Servicio eliminado correctamente',
        ]);
    }

    /**
     * Formatear servicio para respuesta
     */
    private function formatearServicio(Servicio $servicio): array
    {
        return [
            'id' => $servicio->id,
            'categoria_id' => $servicio->categoria_id,
            'nombre' => $servicio->nombre,
            'descripcion' => $servicio->descripcion,
            'precio' => $servicio->precio,
            'precio_texto' => '$' . number_format($servicio->precio, 2),
            'duracion' => $servicio->duracion,
            'duracion_texto' => $this->formatearDuracion($servicio->duracion),
            'active' => $servicio->active,
            'categoria' => $servicio->categoria ? [
                'id' => $servicio->categoria->id,
                'nombre' => $servicio->categoria->nombre,
            ] : null,
        ];
    }

    /**
     * Formatear duración
     */
    private function formatearDuracion(int $minutos): string
    {
        if ($minutos < 60) {
            return "{$minutos} min";
        }
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;
        return $mins > 0 ? "{$horas}h {$mins}min" : "{$horas}h";
    }
}

