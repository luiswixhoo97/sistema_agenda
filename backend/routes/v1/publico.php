<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\PromocionController;
use App\Http\Controllers\Api\AgendamientoPublicoController;
use App\Http\Controllers\Api\DisponibilidadController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\AnticipoController;
use App\Http\Controllers\Api\MercadoPagoController;

// Catálogo público
Route::prefix('publico')->group(function () {
    Route::get('/servicios', [ServicioController::class, 'indexPublico']);
    Route::get('/categorias', [CategoriaController::class, 'indexPublico']);
    Route::get('/empleados', [EmpleadoController::class, 'indexPublico']);
    Route::get('/promociones', [PromocionController::class, 'indexPublico']);
    Route::get('/cliente/telefono/{telefono}', [ClienteController::class, 'buscarPorTelefonoPublico']);

    
    
    // Agendamiento público (sin sesión)
    Route::post('/agendar/otp', [AgendamientoPublicoController::class, 'enviarOtp']);
    Route::post('/agendar', [AgendamientoPublicoController::class, 'agendar']);
    Route::post('/agendar/multiples', [AgendamientoPublicoController::class, 'agendarMultiples']);
    
    // Disponibilidad pública
    Route::get('/disponibilidad/dias', [DisponibilidadController::class, 'diasDisponibles']);
    Route::get('/disponibilidad/slots', [DisponibilidadController::class, 'slotsDisponibles']);
    Route::post('/disponibilidad/slots-coordinados', [DisponibilidadController::class, 'slotsCoordinados']);
    Route::post('/disponibilidad/calcular', [DisponibilidadController::class, 'calcularServicio']);
    
    // Reservas temporales de slots (para evitar conflictos entre usuarios)
    Route::post('/disponibilidad/reservar-temporal', [DisponibilidadController::class, 'reservarTemporal']);
    Route::post('/disponibilidad/reservar-temporal-multiple', [DisponibilidadController::class, 'reservarTemporalMultiple']);
    Route::post('/disponibilidad/liberar-temporal', [DisponibilidadController::class, 'liberarTemporal']);
    Route::post('/disponibilidad/liberar-temporal-multiple', [DisponibilidadController::class, 'liberarTemporalMultiple']);
    Route::post('/disponibilidad/extender-temporal', [DisponibilidadController::class, 'extenderTemporal']);
    Route::get('/disponibilidad/verificar-reserva/{token}', [DisponibilidadController::class, 'verificarReserva']);
    
    // Validación de anticipos
    Route::post('/anticipo/validar', [AnticipoController::class, 'validarPublico']);
    
    // Mercado Pago
    Route::post('/mercadopago/crear-preferencia', [MercadoPagoController::class, 'crearPreferencia']);
    Route::post('/mercadopago/webhook', [MercadoPagoController::class, 'webhook']);
    Route::get('/mercadopago/verificar/{payment_id}', [MercadoPagoController::class, 'verificarPago']);
});