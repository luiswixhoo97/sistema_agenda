<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login para empleados/admin
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Verificar bloqueo
        if (LoginAttempt::estaBloqueado($request->email)) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta bloqueada temporalmente. Intenta en 15 minutos.',
            ], 429);
        }

        $user = User::with('role', 'empleado')
            ->where('email', $request->email)
            ->where('active', true)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            LoginAttempt::registrar($request->email, 'empleado', false);
            
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        // Login exitoso
        LoginAttempt::registrar($request->email, $user->role->nombre, true);

        // Revocar tokens anteriores (opcional)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token', [$user->role->nombre . ':*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'role' => $user->role->nombre,
            ],
            'empleado' => $user->empleado ? [
                'id' => $user->empleado->id,
                'foto' => $user->empleado->foto,
                'bio' => $user->empleado->bio,
            ] : null,
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    /**
     * Obtener usuario actual
     */
    public function me(Request $request)
    {
        $user = $request->user();

        // Si es cliente
        if ($user instanceof \App\Models\Cliente) {
            return response()->json([
                'success' => true,
                'tipo' => 'cliente',
                'usuario' => [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'telefono' => $user->telefono,
                    'email' => $user->email,
                    'notificaciones_push' => $user->notificaciones_push,
                    'notificaciones_email' => $user->notificaciones_email,
                    'notificaciones_whatsapp' => $user->notificaciones_whatsapp,
                ],
            ]);
        }

        // Si es empleado/admin
        $user->load('role', 'empleado');

        return response()->json([
            'success' => true,
            'tipo' => $user->role->nombre,
            'usuario' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'email' => $user->email,
                'telefono' => $user->telefono,
                'role' => $user->role->nombre,
            ],
            'empleado' => $user->empleado ? [
                'id' => $user->empleado->id,
                'foto' => $user->empleado->foto,
                'bio' => $user->empleado->bio,
            ] : null,
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // Eliminar token actual
        $request->user()->currentAccessToken()->delete();

        // Crear nuevo token
        $abilities = $user instanceof \App\Models\Cliente 
            ? ['cliente:*'] 
            : [$user->role->nombre . ':*'];

        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
        ]);
    }
}

