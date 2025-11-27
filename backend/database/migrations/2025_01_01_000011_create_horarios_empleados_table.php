<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->tinyInteger('dia_semana')->comment('1=lunes, 7=domingo');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->boolean('active')->default(true);
            $table->timestamps();
            
            $table->unique(['empleado_id', 'dia_semana'], 'unique_empleado_dia');
            $table->index('empleado_id', 'idx_horarios_empleado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_empleados');
    }
};

