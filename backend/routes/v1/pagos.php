<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PagoController;

// =====================================================
// RUTAS PARA PAGOS (AUTENTICADAS)
// =====================================================
Route::prefix('admin')->middleware('tipo:admin')->group(function () {
    Route::prefix('pagos')->group(function () {
        Route::post('/registrar', [PagoController::class, 'registrarPago']);
        Route::post('/online', [PagoController::class, 'procesarPagoOnline']);
        Route::get('/venta/{venta_id}', [PagoController::class, 'listarPagos']);
        Route::post('/{id}/reembolsar', [PagoController::class, 'reembolsar']);
    });
});
