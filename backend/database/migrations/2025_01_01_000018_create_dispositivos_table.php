<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade')->comment('para empleados/admin');
            $table->string('token_push', 255);
            $table->enum('plataforma', ['android', 'ios', 'web']);
            $table->string('modelo', 100)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->unique('token_push', 'unique_token');
            $table->index('cliente_id', 'idx_dispositivos_cliente');
            $table->index('user_id', 'idx_dispositivos_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositivos');
    }
};

