<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetodoPago;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MetodoPagoController extends Controller
{
    /**
     * Listar métodos de pago activos
     * 
     * GET /api/admin/metodos-pago
     */
    public function index(Request $request): JsonResponse
    {
        $query = MetodoPago::query();

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $metodos = $query->ordenado()->get();

        return response()->json([
            'success' => true,
            'data' => $metodos->map(fn($m) => $this->formatearMetodoPago($m)),
        ]);
    }

    /**
     * Ver método de pago
     * 
     * GET /api/admin/metodos-pago/{id}
     */
    public function show(int $id): JsonResponse
    {
        $metodo = MetodoPago::find($id);

        if (!$metodo) {
            return response()->json([
                'success' => false,
                'message' => 'Método de pago no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatearMetodoPago($metodo),
        ]);
    }

    /**
     * Crear método de pago
     * 
     * POST /api/admin/metodos-pago
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'codigo' => 'required|string|max:50|unique:metodos_pago,codigo',
            'es_efectivo' => 'nullable|boolean',
            'activo' => 'nullable|boolean',
            'orden' => 'nullable|integer|min:0',
        ]);

        $metodo = MetodoPago::create([
            'nombre' => $request->nombre,
            'codigo' => $request->codigo,
            'es_efectivo' => $request->boolean('es_efectivo', false),
            'activo' => $request->boolean('activo', true),
            'orden' => $request->orden ?? 0,
        ]);

        Auditoria::registrar('crear', 'metodos_pago', $metodo->id, null, $metodo->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Método de pago creado correctamente',
            'data' => $this->formatearMetodoPago($metodo),
        ], 201);
    }

    /**
     * Actualizar método de pago
     * 
     * PUT /api/admin/metodos-pago/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $metodo = MetodoPago::find($id);

        if (!$metodo) {
            return response()->json([
                'success' => false,
                'message' => 'Método de pago no encontrado',
            ], 404);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'codigo' => 'sometimes|string|max:50|unique:metodos_pago,codigo,' . $id,
            'es_efectivo' => 'nullable|boolean',
            'activo' => 'nullable|boolean',
            'orden' => 'nullable|integer|min:0',
        ]);

        $datosAnteriores = $metodo->toArray();
        
        $metodo->update($request->only([
            'nombre', 'codigo', 'es_efectivo', 'activo', 'orden'
        ]));

        Auditoria::registrar('actualizar', 'metodos_pago', $metodo->id, $datosAnteriores, $metodo->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Método de pago actualizado correctamente',
            'data' => $this->formatearMetodoPago($metodo),
        ]);
    }

    /**
     * Eliminar método de pago
     * 
     * DELETE /api/admin/metodos-pago/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $metodo = MetodoPago::withCount('ventaPagos')->find($id);

        if (!$metodo) {
            return response()->json([
                'success' => false,
                'message' => 'Método de pago no encontrado',
            ], 404);
        }

        if ($metodo->ventaPagos_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un método de pago que tiene pagos registrados',
            ], 422);
        }

        $datosAnteriores = $metodo->toArray();
        $metodo->delete();

        Auditoria::registrar('eliminar', 'metodos_pago', $id, $datosAnteriores, null);

        return response()->json([
            'success' => true,
            'message' => 'Método de pago eliminado correctamente',
        ]);
    }

    /**
     * Formatear método de pago para respuesta
     */
    private function formatearMetodoPago(MetodoPago $metodo): array
    {
        return [
            'id' => $metodo->id,
            'nombre' => $metodo->nombre,
            'codigo' => $metodo->codigo,
            'es_efectivo' => $metodo->es_efectivo,
            'activo' => $metodo->activo,
            'orden' => $metodo->orden,
        ];
    }
}
