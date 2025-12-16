<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->enum('tipo', ['entrada_manual', 'salida_manual', 'venta']);
            $table->integer('cantidad');
            $table->string('motivo', 255)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable()->comment('ID de venta relacionada');
            $table->string('referencia_tipo', 50)->nullable()->comment('tipo de referencia: venta, etc.');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->timestamp('created_at');
            
            $table->index('producto_id', 'idx_movimientos_producto');
            $table->index('tipo', 'idx_movimientos_tipo');
            $table->index(['referencia_id', 'referencia_tipo'], 'idx_movimientos_referencia');
            $table->index('created_at', 'idx_movimientos_fecha');
            $table->index('user_id', 'idx_movimientos_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
