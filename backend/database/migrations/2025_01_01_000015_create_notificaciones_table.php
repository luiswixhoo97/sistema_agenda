<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas');
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->enum('tipo', ['nueva_cita', 'confirmacion', 'recordatorio', 'recordatorio_dia', 'cancelacion', 'modificacion', 'recordatorio_empleado', 'promocion']);
            $table->enum('medio', ['push', 'email', 'whatsapp']);
            $table->enum('estado', ['pendiente', 'enviada', 'fallida', 'leida'])->default('pendiente');
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('leido_at')->nullable();
            $table->tinyInteger('intentos')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable()->comment('IDs externos, tokens push, message IDs de WhatsApp, etc.');
            $table->timestamps();
            
            $table->index('cita_id', 'idx_notificaciones_cita');
            $table->index('cliente_id', 'idx_notificaciones_cliente');
            $table->index(['estado', 'medio'], 'idx_notificaciones_estado');
            $table->index(['estado', 'created_at'], 'idx_notificaciones_pendientes');
            $table->index(['estado', 'medio', 'created_at'], 'idx_notificaciones_pendientes_medio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};

