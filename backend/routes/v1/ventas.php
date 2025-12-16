<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VentaController;

// =====================================================
// RUTAS PARA VENTAS DIRECTAS (PUNTO DE VENTA)
// =====================================================
Route::prefix('admin')->middleware('tipo:admin')->group(function () {
    Route::prefix('ventas')->group(function () {
        Route::get('/', [VentaController::class, 'index']);
        Route::post('/', [VentaController::class, 'store']);
        Route::post('/calcular-totales', [VentaController::class, 'calcularTotales']);
        Route::get('/productos/buscar', [VentaController::class, 'buscarProductos']);
        Route::get('/{id}', [VentaController::class, 'show']);
        Route::put('/{id}', [VentaController::class, 'update']);
        Route::delete('/{id}', [VentaController::class, 'destroy']);
    });
});
