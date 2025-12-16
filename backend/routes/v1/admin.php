<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ConfiguracionController;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\DisponibilidadController;
use App\Http\Controllers\Api\PromocionController;
use App\Http\Controllers\Api\PlantillaNotificacionController;

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
        Route::post('/citas/{id}/reagendar', [CitaController::class, 'reagendarAdmin']);
        Route::post('/citas/{id}/cancelar', [CitaController::class, 'cancelarAdmin']);
        Route::post('/citas/scan-qr/{token}', [CitaController::class, 'escanearQr']);
        
        // Disponibilidad (para reagendar citas, sin restricción de anticipación)
        Route::get('/disponibilidad/slots', [DisponibilidadController::class, 'slotsDisponiblesEmpleado']);
        
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

        // Plantillas de notificación y comunicaciones
        Route::prefix('plantillas')->group(function () {
            Route::get('/', [PlantillaNotificacionController::class, 'index']);
            Route::get('/{id}', [PlantillaNotificacionController::class, 'show']);
            Route::put('/{id}', [PlantillaNotificacionController::class, 'update']);
            Route::post('/{id}/restablecer', [PlantillaNotificacionController::class, 'restablecer']);
            Route::post('/{id}/preview', [PlantillaNotificacionController::class, 'preview']);
        });

        Route::prefix('comunicaciones')->group(function () {
            Route::post('/enviar', [PlantillaNotificacionController::class, 'enviarComunicacion']);
            Route::get('/clientes', [PlantillaNotificacionController::class, 'getClientes']);
            Route::get('/estadisticas', [PlantillaNotificacionController::class, 'getEstadisticas']);
        });
    });