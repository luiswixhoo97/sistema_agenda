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

        // Verificar si es un cliente (modelo Cliente)
        if ($user instanceof \App\Models\Cliente) {
            if (in_array('cliente', $tiposPermitidos)) {
                return $next($request);
            }
        }

        // Verificar si es un usuario (empleado/admin)
        if ($user instanceof \App\Models\User) {
            $rolUsuario = $user->role->nombre;
            
            if (in_array($rolUsuario, $tiposPermitidos)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para acceder a este recurso',
        ], 403);
    }
}

