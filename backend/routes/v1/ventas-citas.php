<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VentaCitaController;

// =====================================================
// RUTAS PARA VENTAS DESDE CITAS
// =====================================================
Route::prefix('admin')->middleware('tipo:admin')->group(function () {
    Route::prefix('ventas-citas')->group(function () {
        Route::post('/crear-desde-cita/{cita_id}', [VentaCitaController::class, 'crearDesdeCita']);
        Route::post('/{venta_id}/productos', [VentaCitaController::class, 'agregarProductos']);
        Route::post('/{venta_id}/finalizar', [VentaCitaController::class, 'finalizar']);
    });
});
