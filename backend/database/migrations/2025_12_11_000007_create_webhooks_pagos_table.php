<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhooks_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->enum('proveedor', ['mercadopago', 'stripe']);
            $table->string('evento_tipo', 100);
            $table->json('payload');
            $table->boolean('procesado')->default(false);
            $table->json('respuesta')->nullable()->comment('Respuesta enviada al proveedor');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at');
            
            $table->index('venta_id', 'idx_webhooks_venta');
            $table->index('proveedor', 'idx_webhooks_proveedor');
            $table->index('procesado', 'idx_webhooks_procesado');
            $table->index('created_at', 'idx_webhooks_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks_pagos');
    }
};
