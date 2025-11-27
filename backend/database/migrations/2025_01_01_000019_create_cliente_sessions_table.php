<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->unsignedBigInteger('token_id')->comment('ID del personal_access_token de Sanctum');
            $table->json('device_info')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->timestamp('created_at')->nullable();
            
            $table->index('cliente_id', 'idx_sessions_cliente');
            $table->index('last_activity', 'idx_sessions_last_activity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_sessions');
    }
};

