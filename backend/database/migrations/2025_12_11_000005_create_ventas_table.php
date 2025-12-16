<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->dateTime('fecha_venta');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('descuento_general', 10, 2)->default(0);
            $table->decimal('impuesto_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('total_pagado', 10, 2)->default(0)->comment('Total de pagos acumulados');
            $table->decimal('saldo_pendiente', 10, 2)->default(0)->comment('Saldo pendiente (total - total_pagado)');
            $table->enum('estado', ['pendiente_pago', 'parcial', 'completada', 'cancelada'])->default('pendiente_pago');
            
            // Campos para anticipo
            $table->boolean('requiere_anticipo')->default(false);
            $table->decimal('monto_anticipo_requerido', 10, 2)->nullable()->comment('Monto de anticipo requerido');
            $table->decimal('monto_anticipo_pagado', 10, 2)->default(0)->comment('Monto de anticipo pagado');
         
            
            $table->text('notas')->nullable();
           
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('cliente_id', 'idx_ventas_cliente');
            $table->index('fecha_venta', 'idx_ventas_fecha');
            $table->index('estado', 'idx_ventas_estado');
            $table->index('requiere_anticipo', 'idx_ventas_requiere_anticipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
