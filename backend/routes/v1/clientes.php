<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\DisponibilidadController;
use App\Http\Controllers\Api\FotoController;

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
