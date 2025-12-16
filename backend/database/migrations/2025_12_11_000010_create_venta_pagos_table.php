<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago');
            $table->decimal('monto', 10, 2)->comment('Monto del pago');
            $table->decimal('monto_recibido', 10, 2)->nullable()->comment('Para efectivo, monto recibido');
            $table->decimal('cambio', 10, 2)->nullable()->comment('Cambio devuelto');
          
            // Campos para pagos online
            $table->string('transaccion_id', 255)->nullable()->comment('ID de transacción del proveedor de pago');
            $table->enum('proveedor_pago', ['mercadopago', 'stripe'])->nullable();
            $table->enum('estado_pago', ['pendiente', 'aprobado', 'rechazado', 'reembolsado'])->default('pendiente');
            $table->json('metadata_pago')->nullable()->comment('Datos adicionales del pago');
            
            $table->text('notas')->nullable();
            $table->foreignId('user_id')->constrained('users')->comment('Usuario que registró el pago');
            $table->timestamps();
            
            $table->index('venta_id', 'idx_venta_pagos_venta');
            $table->index('metodo_pago_id', 'idx_venta_pagos_metodo_pago');
            $table->index('estado_pago', 'idx_venta_pagos_estado_pago');
            $table->index('transaccion_id', 'idx_venta_pagos_transaccion');
            $table->index('user_id', 'idx_venta_pagos_user');
            $table->index('created_at', 'idx_venta_pagos_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_pagos');
    }
};
