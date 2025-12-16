<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_anticipo_fecha', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regla_anticipo_id')->constrained('reglas_anticipo')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('aplica_todos_dias')->default(true)->comment('Si false, usar dias_semana');
            $table->json('dias_semana')->nullable()->comment('Array de días [1=lunes, 7=domingo], NULL si aplica_todos_dias=true');
            $table->timestamps();
            
            $table->index('regla_anticipo_id', 'idx_reglas_anticipo_fecha_regla');
            $table->index(['fecha_inicio', 'fecha_fin'], 'idx_reglas_anticipo_fecha_rango');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_anticipo_fecha');
    }
};
