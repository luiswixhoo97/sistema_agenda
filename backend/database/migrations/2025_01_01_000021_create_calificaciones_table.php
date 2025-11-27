<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('empleado_id')->constrained('empleados');
            $table->tinyInteger('puntuacion')->comment('1-5 estrellas');
            $table->text('comentario')->nullable();
            $table->boolean('visible')->default(true)->comment('mostrar públicamente');
            $table->timestamps();
            
            $table->unique('cita_id', 'unique_cita_calificacion');
            $table->index('empleado_id', 'idx_calificaciones_empleado');
            $table->index('puntuacion', 'idx_calificaciones_puntuacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};

