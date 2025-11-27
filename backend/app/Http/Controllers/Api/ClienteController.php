<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cita;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    // =====================================================
    // RUTAS PARA EL CLIENTE AUTENTICADO
    // =====================================================

    /**
     * Mi perfil
     * 
     * GET /api/cliente/perfil
     */
    public function miPerfil(Request $request): JsonResponse
    {
        $cliente = $this->getClienteAutenticado($request);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como cliente',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatearCliente($cliente),
        ]);
    }

    /**
     * Actualizar mi perfil
     * 
     * PUT /api/cliente/perfil
     */
    public function actualizarPerfil(Request $request): JsonResponse
    {
        $cliente = $this->getClienteAutenticado($request);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como cliente',
            ], 401);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'email' => 'nullable|email|max:150',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'preferencia_contacto' => 'sometimes|in:whatsapp,sms,email,llamada',
        ]);

        $datosAnteriores = $cliente->toArray();

        $cliente->update($request->only([
            'nombre', 'email', 'fecha_nacimiento', 'preferencia_contacto'
        ]));

        Auditoria::registrar('actualizar', 'clientes', $cliente->id, $datosAnteriores, $cliente->fresh()->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'data' => $this->formatearCliente($cliente->fresh()),
        ]);
    }

    /**
     * Actualizar preferencias de notificaciones
     * 
     * PUT /api/cliente/perfil/notificaciones
     */
    public function actualizarNotificaciones(Request $request): JsonResponse
    {
        $cliente = $this->getClienteAutenticado($request);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado como cliente',
            ], 401);
        }

        $request->validate([
            'notificaciones_push' => 'sometimes|boolean',
            'notificaciones_email' => 'sometimes|boolean',
            'notificaciones_whatsapp' => 'sometimes|boolean',
        ]);

        $cliente->update($request->only([
            'notificaciones_push', 'notificaciones_email', 'notificaciones_whatsapp'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Preferencias de notificaciones actualizadas',
            'data' => [
                'notificaciones_push' => $cliente->notificaciones_push,
                'notificaciones_email' => $cliente->notificaciones_email,
                'notificaciones_whatsapp' => $cliente->notificaciones_whatsapp,
            ],
        ]);
    }

    // =====================================================
    // RUTAS PARA ADMIN
    // =====================================================

    /**
     * Listado de clientes
     * 
     * GET /api/admin/clientes
     */
    public function index(Request $request): JsonResponse
    {
        $query = Cliente::withCount('citas');

        // Búsqueda
        if ($request->has('buscar')) {
            $query->buscar($request->buscar);
        }

        // Filtros
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $clientes = $query->orderBy('nombre')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $clientes->map(fn($c) => $this->formatearCliente($c)),
            'pagination' => [
                'current_page' => $clientes->currentPage(),
                'last_page' => $clientes->lastPage(),
                'total' => $clientes->total(),
            ],
        ]);
    }

    /**
     * Ver cliente
     * 
     * GET /api/admin/clientes/{id}
     */
    public function show(int $id): JsonResponse
    {
        $cliente = Cliente::withCount('citas')
            ->with(['citas' => fn($q) => $q->latest()->limit(10)])
            ->find($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        $data = $this->formatearCliente($cliente);
        $data['ultimas_citas'] = $cliente->citas->map(fn($c) => [
            'id' => $c->id,
            'fecha_hora' => $c->fecha_hora->format('Y-m-d H:i'),
            'estado' => $c->estado,
            'precio_final' => $c->precio_final,
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Crear cliente
     * 
     * POST /api/admin/clientes
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'telefono' => 'required|string|max:20',
            'email' => 'nullable|email|max:150',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'notas' => 'nullable|string',
            'preferencia_contacto' => 'nullable|in:whatsapp,sms,email,llamada',
        ]);

        // Verificar teléfono único
        if (Cliente::where('telefono', $request->telefono)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un cliente con este teléfono',
            ], 422);
        }

        $cliente = Cliente::create([
            'nombre' => $request->nombre,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'notas' => $request->notas,
            'preferencia_contacto' => $request->preferencia_contacto ?? 'whatsapp',
            'notificaciones_push' => true,
            'notificaciones_email' => true,
            'notificaciones_whatsapp' => true,
            'active' => true,
        ]);

        Auditoria::registrar('crear', 'clientes', $cliente->id, null, $cliente->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado correctamente',
            'data' => $this->formatearCliente($cliente),
        ], 201);
    }

    /**
     * Actualizar cliente
     * 
     * PUT /api/admin/clientes/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'telefono' => 'sometimes|string|max:20|unique:clientes,telefono,' . $id,
            'email' => 'nullable|email|max:150',
            'fecha_nacimiento' => 'nullable|date|before:today',
            'notas' => 'nullable|string',
            'preferencia_contacto' => 'sometimes|in:whatsapp,sms,email,llamada',
            'notificaciones_push' => 'sometimes|boolean',
            'notificaciones_email' => 'sometimes|boolean',
            'notificaciones_whatsapp' => 'sometimes|boolean',
            'active' => 'sometimes|boolean',
        ]);

        $datosAnteriores = $cliente->toArray();

        $cliente->update($request->only([
            'nombre', 'telefono', 'email', 'fecha_nacimiento', 'notas',
            'preferencia_contacto', 'notificaciones_push', 'notificaciones_email',
            'notificaciones_whatsapp', 'active'
        ]));

        Auditoria::registrar('actualizar', 'clientes', $cliente->id, $datosAnteriores, $cliente->fresh()->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado correctamente',
            'data' => $this->formatearCliente($cliente->fresh()),
        ]);
    }

    /**
     * Eliminar cliente (soft delete)
     * 
     * DELETE /api/admin/clientes/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $cliente = Cliente::withCount(['citas' => fn($q) => $q->whereIn('estado', ['pendiente', 'confirmada'])])->find($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        // No permitir eliminar si tiene citas activas
        if ($cliente->citas_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un cliente con citas pendientes',
            ], 422);
        }

        $datosAnteriores = $cliente->toArray();
        $cliente->delete();

        Auditoria::registrar('eliminar', 'clientes', $id, $datosAnteriores, null);

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado correctamente',
        ]);
    }

    /**
     * Citas de un cliente
     * 
     * GET /api/admin/clientes/{id}/citas
     */
    public function citasDelCliente(Request $request, int $id): JsonResponse
    {
        $cliente = Cliente::find($id);

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        $query = Cita::with(['empleado.user', 'servicio'])
            ->where('cliente_id', $id);

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $citas = $query->orderBy('fecha_hora', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $citas->map(fn($c) => [
                'id' => $c->id,
                'fecha_hora' => $c->fecha_hora->format('Y-m-d H:i'),
                'estado' => $c->estado,
                'precio_final' => $c->precio_final,
                'empleado' => $c->empleado ? $c->empleado->user->nombre : null,
                'servicio' => $c->servicio->nombre,
            ]),
            'pagination' => [
                'current_page' => $citas->currentPage(),
                'last_page' => $citas->lastPage(),
                'total' => $citas->total(),
            ],
        ]);
    }

    // =====================================================
    // HELPERS
    // =====================================================

    /**
     * Obtener cliente autenticado
     */
    private function getClienteAutenticado(Request $request): ?Cliente
    {
        $user = $request->user();
        return $user instanceof Cliente ? $user : null;
    }

    /**
     * Formatear cliente para respuesta
     */
    private function formatearCliente(Cliente $cliente): array
    {
        return [
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
            'telefono' => $cliente->telefono,
            'email' => $cliente->email,
            'fecha_nacimiento' => $cliente->fecha_nacimiento?->format('Y-m-d'),
            'notas' => $cliente->notas,
            'preferencia_contacto' => $cliente->preferencia_contacto,
            'notificaciones_push' => $cliente->notificaciones_push,
            'notificaciones_email' => $cliente->notificaciones_email,
            'notificaciones_whatsapp' => $cliente->notificaciones_whatsapp,
            'active' => $cliente->active,
            'citas_count' => $cliente->citas_count ?? 0,
            'created_at' => $cliente->created_at?->format('Y-m-d'),
        ];
    }
}

