<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueos_tiempo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->onDelete('cascade')->comment('NULL = bloqueo para todos los empleados');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('motivo', 255)->nullable();
            $table->enum('tipo', ['almuerzo', 'limpieza', 'libre', 'reunion', 'otro']);
            $table->timestamps();
            
            $table->index(['fecha', 'empleado_id'], 'idx_bloqueos_fecha_empleado');
            $table->index('empleado_id', 'idx_bloqueos_empleado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_tiempo');
    }
};

