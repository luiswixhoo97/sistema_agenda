<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mercado_pago_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->nullable()->unique(); // ID del pago en Mercado Pago
            $table->string('preference_id')->nullable(); // ID de la preferencia
            $table->string('external_reference')->index(); // Referencia externa (cita_id, venta_id, etc)
            $table->decimal('monto', 10, 2);
            $table->string('estado')->default('pending'); // pending, approved, rejected, cancelled, refunded
            $table->string('payer_email')->nullable();
            $table->json('datos_pago')->nullable(); // Datos completos del pago
            $table->timestamp('fecha_aprobacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_pago_pagos');
    }
};
