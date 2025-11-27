<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->onDelete('cascade');
            $table->foreignId('servicio_id')->constrained('servicios');
            $table->decimal('precio_aplicado', 10, 2);
            $table->tinyInteger('orden')->default(1)->comment('orden de ejecución del servicio');
            $table->timestamps();
            
            $table->index('cita_id', 'idx_citas_servicios_cita');
            $table->index('servicio_id', 'idx_citas_servicios_servicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas_servicios');
    }
};

