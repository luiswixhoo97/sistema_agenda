<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('identificador', 255)->comment('email o teléfono');
            $table->enum('tipo', ['empleado', 'cliente', 'admin']);
            $table->string('ip_address', 45)->nullable();
            $table->boolean('exitoso')->default(false);
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
            
            $table->index('identificador', 'idx_attempts_identificador');
            $table->index('ip_address', 'idx_attempts_ip');
            $table->index('created_at', 'idx_attempts_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};

