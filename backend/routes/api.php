<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteAuthController;
use App\Http\Controllers\Api\DispositivoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// =====================================================
// RUTAS PÚBLICAS (sin autenticación)
// =====================================================

// Autenticación de empleados/admin
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Autenticación de clientes (OTP)
Route::prefix('auth/cliente')->group(function () {
    Route::post('/otp/solicitar', [ClienteAuthController::class, 'solicitarOtp']);
    Route::post('/otp/verificar', [ClienteAuthController::class, 'verificarOtp']);
    Route::post('/registrar', [ClienteAuthController::class, 'registrar']);
});

include __DIR__ . '/v1/publico.php';

// Webhooks públicos (sin autenticación)
Route::prefix('webhooks')->group(function () {
    Route::post('/mercadopago', [\App\Http\Controllers\Api\PagoController::class, 'webhookMercadoPago']);
    Route::post('/stripe', [\App\Http\Controllers\Api\PagoController::class, 'webhookStripe']);
});


// =====================================================
// RUTAS AUTENTICADAS
// =====================================================

Route::middleware('auth:sanctum')->group(function () {
    
    // Logout y verificación de sesión
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    // Dispositivos (push tokens)
    Route::post('/dispositivos/registrar', [DispositivoController::class, 'registrar']);
    Route::delete('/dispositivos/{token}', [DispositivoController::class, 'eliminar']);

    include __DIR__ . '/v1/clientes.php';

    include __DIR__ . '/v1/empleado.php';

    include __DIR__ . '/v1/admin.php';

    // Sistema de ventas e inventario
    include __DIR__ . '/v1/productos.php';
    include __DIR__ . '/v1/inventario.php';
    include __DIR__ . '/v1/ventas.php';
    include __DIR__ . '/v1/ventas-citas.php';
    include __DIR__ . '/v1/metodos-pago.php';
    include __DIR__ . '/v1/anticipos.php';
    include __DIR__ . '/v1/pagos.php';

    
});

