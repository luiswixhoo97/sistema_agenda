<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoriaProductoController;
use App\Http\Controllers\Api\ProductoController;

// =====================================================
// RUTAS PARA PRODUCTOS Y CATEGORÍAS DE PRODUCTOS
// =====================================================
Route::prefix('admin')->middleware('tipo:admin')->group(function () {
    
    // Categorías de productos
    Route::prefix('categorias-productos')->group(function () {
        Route::get('/', [CategoriaProductoController::class, 'index']);
        Route::post('/', [CategoriaProductoController::class, 'store']);
        Route::get('/{id}', [CategoriaProductoController::class, 'show']);
        Route::put('/{id}', [CategoriaProductoController::class, 'update']);
        Route::delete('/{id}', [CategoriaProductoController::class, 'destroy']);
    });

    // Productos
    Route::prefix('productos')->group(function () {
        Route::get('/', [ProductoController::class, 'index']);
        Route::post('/', [ProductoController::class, 'store']);
        Route::get('/buscar', [ProductoController::class, 'buscar']);
        Route::get('/{id}', [ProductoController::class, 'show']);
        Route::put('/{id}', [ProductoController::class, 'update']);
        Route::delete('/{id}', [ProductoController::class, 'destroy']);
        Route::post('/{id}/qr', [ProductoController::class, 'generarQr']);
    });
});
