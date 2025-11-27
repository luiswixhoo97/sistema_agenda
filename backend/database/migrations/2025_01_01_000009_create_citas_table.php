<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('empleado_id')->constrained('empleados');
            $table->foreignId('servicio_id')->constrained('servicios');
            $table->foreignId('promocion_id')->nullable()->constrained('promociones');
            $table->dateTime('fecha_hora');
            $table->integer('duracion_total')->comment('duración total en minutos');
            $table->enum('estado', ['pendiente', 'confirmada', 'en_proceso', 'completada', 'cancelada', 'no_show'])->default('pendiente');
            $table->decimal('precio_final', 10, 2)->comment('precio con descuento aplicado');
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia', 'pendiente'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['fecha_hora', 'empleado_id'], 'idx_citas_fecha_empleado');
            $table->index(['cliente_id', 'estado'], 'idx_citas_cliente_estado');
            $table->index(['fecha_hora', 'estado'], 'idx_citas_fecha_estado');
            $table->index('empleado_id', 'idx_citas_empleado');
            $table->index(['fecha_hora', 'estado', 'empleado_id', 'deleted_at'], 'idx_citas_disponibilidad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};

