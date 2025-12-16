<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_anticipo_monto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_anticipo_id')->constrained('reglas_anticipo')->cascadeOnDelete();
            $table->decimal('monto_minimo', 10, 2)->comment('Ventas >= este monto requieren anticipo');
            $table->decimal('monto_maximo', 10, 2)->nullable()->comment('Ventas <= este monto (NULL = sin límite)');
            $table->timestamps();
            
            $table->index('regla_anticipo_id', 'idx_reglas_anticipo_monto_regla');
            $table->index('monto_minimo', 'idx_reglas_anticipo_monto_minimo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_anticipo_monto');
    }
};
