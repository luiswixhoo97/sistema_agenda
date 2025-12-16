<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\DisponibilidadController;
use App\Http\Controllers\Api\FotoController;

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
        Route::post('/citas/{id}/reagendar', [CitaController::class, 'reagendarEmpleado']);
        Route::post('/citas/{id}/cancelar', [CitaController::class, 'cancelarEmpleado']);
        Route::post('/citas/{id}/fotos', [CitaController::class, 'subirFoto']);
        Route::post('/citas/scan-qr/{token}', [CitaController::class, 'escanearQr']);
        
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