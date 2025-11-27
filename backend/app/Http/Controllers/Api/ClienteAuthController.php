<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\OtpCode;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;

class ClienteAuthController extends Controller
{
    /**
     * Solicitar OTP para login/registro
     */
    public function solicitarOtp(Request $request)
    {
        $request->validate([
            'telefono' => 'required|string|min:10|max:20',
        ]);

        $telefono = $this->normalizarTelefono($request->telefono);

        // Verificar rate limiting
        $intentosRecientes = OtpCode::where('telefono', $telefono)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($intentosRecientes >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiadas solicitudes. Intenta en 1 hora.',
            ], 429);
        }

        // Verificar si el cliente existe
        $cliente = Cliente::where('telefono', $telefono)->first();

        // Generar OTP
        $otp = OtpCode::generar($telefono);

        // TODO: Enviar OTP por WhatsApp/SMS
        // WhatsAppService::enviarOtp($telefono, $otp->codigo);

        return response()->json([
            'success' => true,
            'es_nuevo' => is_null($cliente),
            'mensaje' => $cliente 
                ? 'Código enviado a tu teléfono' 
                : 'Código enviado. Completa tu registro.',
            'expira_en' => OtpCode::MINUTOS_EXPIRACION * 60,
            'reenviar_en' => 60,
            // Solo para desarrollo - QUITAR EN PRODUCCIÓN
            'codigo_debug' => config('app.debug') ? $otp->codigo : null,
        ]);
    }

    /**
     * Verificar OTP y hacer login
     */
    public function verificarOtp(Request $request)
    {
        $request->validate([
            'telefono' => 'required|string|min:10|max:20',
            'codigo' => 'required|string|size:6',
        ]);

        $telefono = $this->normalizarTelefono($request->telefono);

        // Verificar bloqueo
        if (LoginAttempt::estaBloqueado($telefono)) {
            return response()->json([
                'success' => false,
                'message' => 'Cuenta bloqueada temporalmente. Intenta en 15 minutos.',
            ], 429);
        }

        // Verificar OTP
        $resultado = OtpCode::verificar($telefono, $request->codigo);

        if (!$resultado['valido']) {
            LoginAttempt::registrar($telefono, 'cliente', false);
            
            return response()->json([
                'success' => false,
                'message' => $resultado['error'],
                'intentos_restantes' => $resultado['intentos_restantes'],
            ], 401);
        }

        // OTP válido - buscar o crear cliente
        $cliente = Cliente::where('telefono', $telefono)->first();

        if (!$cliente) {
            // Cliente nuevo - necesita registrarse
            return response()->json([
                'success' => true,
                'requiere_registro' => true,
                'telefono' => $telefono,
                'mensaje' => 'Código verificado. Completa tu registro.',
            ]);
        }

        // Cliente existente - hacer login
        LoginAttempt::registrar($telefono, 'cliente', true);

        $token = $cliente->createToken('auth_token', ['cliente:*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'requiere_registro' => false,
            'token' => $token,
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'telefono' => $cliente->telefono,
                'email' => $cliente->email,
            ],
        ]);
    }

    /**
     * Registrar nuevo cliente
     */
    public function registrar(Request $request)
    {
        $request->validate([
            'telefono' => 'required|string|min:10|max:20',
            'codigo' => 'required|string|size:6',
            'nombre' => 'required|string|max:100',
            'email' => 'nullable|email|max:150',
            'fecha_nacimiento' => 'nullable|date|before:today',
        ]);

        $telefono = $this->normalizarTelefono($request->telefono);

        // Verificar OTP nuevamente
        $resultado = OtpCode::verificar($telefono, $request->codigo);

        if (!$resultado['valido']) {
            return response()->json([
                'success' => false,
                'message' => $resultado['error'],
            ], 401);
        }

        // Verificar que el cliente no exista
        if (Cliente::where('telefono', $telefono)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Este teléfono ya está registrado',
            ], 422);
        }

        // Crear cliente
        $cliente = Cliente::create([
            'nombre' => $request->nombre,
            'telefono' => $telefono,
            'email' => $request->email,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'preferencia_contacto' => 'whatsapp',
            'notificaciones_push' => true,
            'notificaciones_email' => true,
            'notificaciones_whatsapp' => true,
        ]);

        LoginAttempt::registrar($telefono, 'cliente', true);

        $token = $cliente->createToken('auth_token', ['cliente:*'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registro exitoso',
            'token' => $token,
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'telefono' => $cliente->telefono,
                'email' => $cliente->email,
            ],
        ], 201);
    }

    /**
     * Normalizar formato de teléfono
     */
    private function normalizarTelefono(string $telefono): string
    {
        // Eliminar espacios y caracteres especiales
        $telefono = preg_replace('/[^0-9+]/', '', $telefono);

        // Si no tiene código de país, agregar +52 (México)
        if (!str_starts_with($telefono, '+')) {
            if (strlen($telefono) === 10) {
                $telefono = '+52' . $telefono;
            }
        }

        return $telefono;
    }
}

