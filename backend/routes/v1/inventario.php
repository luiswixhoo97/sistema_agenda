<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InventarioController;

// =====================================================
// RUTAS PARA INVENTARIO
// =====================================================
Route::prefix('admin')->middleware('tipo:admin')->group(function () {
    Route::prefix('inventario')->group(function () {
        Route::post('/entrada', [InventarioController::class, 'entradaManual']);
        Route::post('/salida', [InventarioController::class, 'salidaManual']);
        Route::post('/ajuste', [InventarioController::class, 'ajuste']);
        Route::get('/kardex/{producto_id}', [InventarioController::class, 'kardex']);
        Route::get('/movimientos', [InventarioController::class, 'movimientos']);
    });
});
