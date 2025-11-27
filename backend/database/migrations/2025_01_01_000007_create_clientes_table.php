<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('telefono', 20);
            $table->string('email', 150)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->text('notas')->nullable();
            $table->enum('preferencia_contacto', ['whatsapp', 'sms', 'email', 'llamada'])->default('whatsapp');
            $table->boolean('notificaciones_push')->default(true);
            $table->boolean('notificaciones_email')->default(true);
            $table->boolean('notificaciones_whatsapp')->default(true);
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('telefono', 'idx_clientes_telefono');
            $table->index('email', 'idx_clientes_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};

