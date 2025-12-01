<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TipoUsuario
{
    /**
     * Verificar tipo de usuario
     * 
     * @param string $tipos Tipos permitidos separados por coma (ej: "admin,empleado")
     */
    public function handle(Request $request, Closure $next, string $tipos): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        $tiposPermitidos = explode(',', $tipos);
        $tiposPermitidos = array_map('trim', $tiposPermitidos);

        // Verificar si es un cliente (modelo Cliente)
        if ($user instanceof \App\Models\Cliente) {
            if (in_array('cliente', $tiposPermitidos)) {
                return $next($request);
            }
        }

        // Verificar si es un usuario (empleado/admin)
        if ($user instanceof \App\Models\User) {
            // Cargar la relación role si no está cargada
            if (!$user->relationLoaded('role')) {
                $user->load('role');
            }
            
            // Verificar si tiene role
            if (!$user->role) {
                \Log::warning('Usuario sin rol asignado', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario sin rol asignado',
                ], 403);
            }
            
            $rolUsuario = $user->role->nombre;
            
            \Log::info('Verificando tipo de usuario', [
                'user_id' => $user->id,
                'rol' => $rolUsuario,
                'tipos_permitidos' => $tiposPermitidos,
                'tiene_acceso' => in_array($rolUsuario, $tiposPermitidos)
            ]);
            
            if (in_array($rolUsuario, $tiposPermitidos)) {
                return $next($request);
            }
        }

        \Log::warning('Acceso denegado por tipo de usuario', [
            'user_id' => $user->id ?? null,
            'user_type' => get_class($user),
            'tipos_permitidos' => $tiposPermitidos
        ]);

        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para acceder a este recurso',
        ], 403);
    }
}

