<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispositivo;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DispositivoController extends Controller
{
    /**
     * Registrar dispositivo para push notifications
     * 
     * POST /api/dispositivos/registrar
     */
    public function registrar(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|max:255',
            'plataforma' => 'required|in:android,ios,web',
            'modelo' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        // Determinar si es cliente o empleado
        $clienteId = null;
        $userId = null;

        if ($user instanceof Cliente) {
            $clienteId = $user->id;
        } elseif ($user instanceof \App\Models\User) {
            $userId = $user->id;
        }

        // Verificar si el token ya existe
        $dispositivo = Dispositivo::where('token_push', $request->token)->first();

        if ($dispositivo) {
            // Actualizar dispositivo existente
            $dispositivo->update([
                'cliente_id' => $clienteId,
                'user_id' => $userId,
                'plataforma' => $request->plataforma,
                'modelo' => $request->modelo,
                'activo' => true,
                'last_used_at' => now(),
            ]);
        } else {
            // Crear nuevo dispositivo
            $dispositivo = Dispositivo::create([
                'cliente_id' => $clienteId,
                'user_id' => $userId,
                'token_push' => $request->token,
                'plataforma' => $request->plataforma,
                'modelo' => $request->modelo,
                'activo' => true,
                'last_used_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo registrado correctamente',
            'data' => [
                'id' => $dispositivo->id,
                'plataforma' => $dispositivo->plataforma,
            ],
        ]);
    }

    /**
     * Eliminar/desactivar dispositivo
     * 
     * DELETE /api/dispositivos/{token}
     */
    public function eliminar(Request $request, string $token): JsonResponse
    {
        $dispositivo = Dispositivo::where('token_push', $token)->first();

        if (!$dispositivo) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo no encontrado',
            ], 404);
        }

        // Verificar que el dispositivo pertenece al usuario
        $user = $request->user();
        $autorizado = false;

        if ($user instanceof Cliente && $dispositivo->cliente_id === $user->id) {
            $autorizado = true;
        } elseif ($user instanceof \App\Models\User && $dispositivo->user_id === $user->id) {
            $autorizado = true;
        }

        if (!$autorizado) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $dispositivo->update(['activo' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo eliminado correctamente',
        ]);
    }
}

