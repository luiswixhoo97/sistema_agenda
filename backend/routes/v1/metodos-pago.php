<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MetodoPagoController;

// =====================================================
// RUTAS PARA MÉTODOS DE PAGO
// =====================================================
Route::prefix('admin')->middleware('tipo:admin')->group(function () {
    Route::apiResource('metodos-pago', MetodoPagoController::class);
});

// Empleados también pueden ver métodos de pago para crear ventas parciales
Route::prefix('empleado')->middleware('tipo:empleado')->group(function () {
    Route::get('/metodos-pago', [MetodoPagoController::class, 'index']);
});
