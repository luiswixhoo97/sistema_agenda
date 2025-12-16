<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_anticipo_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_anticipo_id')->constrained('reglas_anticipo')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->timestamps();
            
            $table->index('regla_anticipo_id', 'idx_reglas_anticipo_servicio_regla');
            $table->index('servicio_id', 'idx_reglas_anticipo_servicio_servicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_anticipo_servicio');
    }
};
