<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AnticipoController;

// =====================================================
// RUTAS PARA CONFIGURACIÓN DE ANTICIPOS
// =====================================================
Route::prefix('admin')->middleware('tipo:admin')->group(function () {
    Route::prefix('anticipos')->group(function () {
        Route::get('/reglas', [AnticipoController::class, 'index']);
        Route::post('/reglas', [AnticipoController::class, 'store']);
        Route::get('/reglas/{id}', [AnticipoController::class, 'show']);
        Route::put('/reglas/{id}', [AnticipoController::class, 'update']);
        Route::delete('/reglas/{id}', [AnticipoController::class, 'destroy']);
        Route::post('/calcular', [AnticipoController::class, 'calcular']);
        Route::post('/evaluar', [AnticipoController::class, 'evaluar']);
    });
});
