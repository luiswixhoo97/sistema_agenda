<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteAuthController;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\DisponibilidadController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\PromocionController;
use App\Http\Controllers\Api\ConfiguracionController;
use App\Http\Controllers\Api\DispositivoController;
use App\Http\Controllers\Api\FotoController;
use App\Http\Controllers\Api\AgendamientoPublicoController;

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

// Catálogo público
Route::prefix('publico')->group(function () {
    Route::get('/servicios', [ServicioController::class, 'indexPublico']);
    Route::get('/categorias', [CategoriaController::class, 'indexPublico']);
    Route::get('/empleados', [EmpleadoController::class, 'indexPublico']);
    Route::get('/promociones', [PromocionController::class, 'indexPublico']);
    
    // Agendamiento público (sin sesión)
    Route::post('/agendar/otp', [AgendamientoPublicoController::class, 'enviarOtp']);
    Route::post('/agendar', [AgendamientoPublicoController::class, 'agendar']);
    
    // Disponibilidad pública
    Route::get('/disponibilidad/dias', [DisponibilidadController::class, 'diasDisponibles']);
    Route::get('/disponibilidad/slots', [DisponibilidadController::class, 'slotsDisponibles']);
    Route::post('/disponibilidad/calcular', [DisponibilidadController::class, 'calcularServicio']);
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

    // =====================================================
    // RUTAS PARA CLIENTES
    // =====================================================
    Route::prefix('cliente')->group(function () {
        // Perfil
        Route::get('/perfil', [ClienteController::class, 'miPerfil']);
        Route::put('/perfil', [ClienteController::class, 'actualizarPerfil']);
        Route::put('/perfil/notificaciones', [ClienteController::class, 'actualizarNotificaciones']);

        // Citas del cliente
        Route::get('/citas', [CitaController::class, 'misCitas']);
        Route::get('/citas/{id}', [CitaController::class, 'verMiCita']);
        Route::post('/citas', [CitaController::class, 'agendar']);
        Route::put('/citas/{id}', [CitaController::class, 'modificarMiCita']);
        Route::post('/citas/{id}/cancelar', [CitaController::class, 'cancelarMiCita']);

        // Disponibilidad
        Route::get('/disponibilidad/dias', [DisponibilidadController::class, 'diasDisponibles']);
        Route::get('/disponibilidad/slots', [DisponibilidadController::class, 'slotsDisponibles']);
        Route::post('/disponibilidad/verificar', [DisponibilidadController::class, 'verificar']);
        Route::get('/disponibilidad/empleados', [DisponibilidadController::class, 'empleadosDisponibles']);
        Route::post('/disponibilidad/calcular', [DisponibilidadController::class, 'calcularServicio']);

        // Calificaciones
        Route::post('/citas/{id}/calificar', [CitaController::class, 'calificar']);

        // Fotos
        Route::get('/citas/{id}/fotos', [FotoController::class, 'index']);
        Route::post('/citas/{id}/fotos', [FotoController::class, 'subir']);
        Route::get('/galeria', [FotoController::class, 'galeriaCliente']);
    });

    // =====================================================
    // RUTAS PARA EMPLEADOS
    // =====================================================
    Route::prefix('empleado')->middleware('tipo:empleado,admin')->group(function () {
        // Calendario
        Route::get('/calendario/dia', [CitaController::class, 'citasDelDia']);
        Route::get('/calendario/semana', [CitaController::class, 'citasDeLaSemana']);

        // Gestión de citas
        Route::get('/citas', [CitaController::class, 'misCitasEmpleado']);
        Route::post('/citas', [CitaController::class, 'storeEmpleado']);
        Route::put('/citas/{id}/estado', [CitaController::class, 'cambiarEstado']);
        Route::post('/citas/{id}/fotos', [CitaController::class, 'subirFoto']);
        
        // Mi perfil de empleado
        Route::get('/perfil', [EmpleadoController::class, 'miPerfil']);
        Route::put('/perfil', [EmpleadoController::class, 'actualizarMiPerfil']);
        Route::get('/estadisticas', [EmpleadoController::class, 'misEstadisticas']);

        // Mis servicios asignados
        Route::get('/mis-servicios', [EmpleadoController::class, 'misServicios']);

        // Clientes (para crear citas)
        Route::get('/clientes', [ClienteController::class, 'indexEmpleado']);
        Route::post('/clientes', [ClienteController::class, 'storeEmpleado']);

        // Horarios
        Route::get('/horarios', [EmpleadoController::class, 'misHorarios']);
        Route::put('/horarios', [EmpleadoController::class, 'actualizarHorarios']);

        // Bloqueos
        Route::get('/bloqueos', [EmpleadoController::class, 'misBloqueos']);
        Route::post('/bloqueos', [EmpleadoController::class, 'crearBloqueo']);
        Route::delete('/bloqueos/{id}', [EmpleadoController::class, 'eliminarBloqueo']);

        // Disponibilidad (sin restricción de anticipación mínima)
        Route::get('/disponibilidad/slots', [DisponibilidadController::class, 'slotsDisponiblesEmpleado']);

        // Fotos de citas
        Route::get('/citas/{id}/fotos', [FotoController::class, 'index']);
        Route::post('/citas/{id}/fotos', [FotoController::class, 'subir']);
        Route::delete('/fotos/{id}', [FotoController::class, 'destroy']);
    });

    // =====================================================
    // RUTAS PARA ADMIN
    // =====================================================
    Route::prefix('admin')->middleware('tipo:admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [ConfiguracionController::class, 'dashboard']);

        // Servicios
        Route::apiResource('servicios', ServicioController::class);
        
        // Categorías
        Route::apiResource('categorias', CategoriaController::class);
        
        // Empleados
        Route::apiResource('empleados', EmpleadoController::class);
        Route::get('/empleados/{id}/horarios', [EmpleadoController::class, 'horarios']);
        Route::put('/empleados/{id}/horarios', [EmpleadoController::class, 'actualizarHorarios']);
        Route::get('/empleados/{id}/servicios', [EmpleadoController::class, 'servicios']);
        Route::put('/empleados/{id}/servicios', [EmpleadoController::class, 'asignarServicios']);

        // Clientes
        Route::apiResource('clientes', ClienteController::class);
        Route::get('/clientes/{id}/citas', [ClienteController::class, 'citasDelCliente']);

        // Citas
        Route::apiResource('citas', CitaController::class);
        Route::get('/citas-calendario', [CitaController::class, 'calendario']);
        
        // Promociones
        Route::apiResource('promociones', PromocionController::class);

        // Configuración
        Route::get('/configuracion', [ConfiguracionController::class, 'index']);
        Route::put('/configuracion', [ConfiguracionController::class, 'actualizar']);

        // Reportes
        Route::prefix('reportes')->group(function () {
            Route::get('/citas', [ConfiguracionController::class, 'reporteCitas']);
            Route::get('/ingresos', [ConfiguracionController::class, 'reporteIngresos']);
            Route::get('/empleados', [ConfiguracionController::class, 'reporteEmpleados']);
            Route::get('/servicios', [ConfiguracionController::class, 'reporteServicios']);
        });

        // Auditoría
        Route::get('/auditoria', [ConfiguracionController::class, 'auditoria']);

        // Días festivos
        Route::apiResource('dias-festivos', ConfiguracionController::class . '@diasFestivos');
    });
});

