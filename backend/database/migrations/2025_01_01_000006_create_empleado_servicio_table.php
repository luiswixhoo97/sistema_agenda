<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleado_servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');
            $table->decimal('precio_especial', 10, 2)->nullable()->comment('precio específico del empleado, NULL = precio estándar');
            $table->timestamps();
            
            $table->unique(['empleado_id', 'servicio_id'], 'unique_empleado_servicio');
            $table->index('empleado_id', 'idx_empleado_servicio_empleado');
            $table->index('servicio_id', 'idx_empleado_servicio_servicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleado_servicio');
    }
};

