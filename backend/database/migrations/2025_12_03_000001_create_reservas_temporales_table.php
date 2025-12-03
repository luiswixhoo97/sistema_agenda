<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta tabla guarda reservas temporales de slots de horario
     * para prevenir conflictos cuando múltiples usuarios
     * intentan reservar el mismo horario al mismo tiempo.
     */
    public function up(): void
    {
        Schema::create('reservas_temporales', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique(); // Token único para identificar la reserva
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->integer('duracion_total'); // Duración total en minutos
            $table->string('telefono_cliente', 20)->nullable(); // Para identificar al usuario
            $table->string('session_id', 100)->nullable(); // ID de sesión del navegador
            $table->json('servicios_ids')->nullable(); // IDs de servicios reservados
            $table->json('datos_adicionales')->nullable(); // Datos extra para slots coordinados
            $table->timestamp('expira_at'); // Cuándo expira la reserva temporal
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index(['empleado_id', 'fecha', 'hora_inicio']);
            $table->index('expira_at');
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas_temporales');
    }
};

