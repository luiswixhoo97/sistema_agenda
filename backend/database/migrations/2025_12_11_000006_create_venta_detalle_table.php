<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->enum('tipo', ['servicio', 'producto'])->default('producto');
            
            // Campos para productos
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            
            // Campos para servicios
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete()->comment('Cita asociada al servicio');
          
            
            // Promoción aplicada
            $table->foreignId('promocion_id')->nullable()->constrained('promociones')->nullOnDelete();
            
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0)->comment('Descuento aplicado a esta línea');
            $table->decimal('impuesto', 10, 2)->default(0)->comment('Impuesto aplicado a esta línea');
            $table->decimal('subtotal_linea', 10, 2)->comment('Subtotal de la línea (cantidad * precio_unitario - descuento + impuesto)');
            $table->timestamps();
            
            $table->index('venta_id', 'idx_venta_detalle_venta');
            $table->index('tipo', 'idx_venta_detalle_tipo');
            $table->index('producto_id', 'idx_venta_detalle_producto');
            $table->index('servicio_id', 'idx_venta_detalle_servicio');
            $table->index('cita_id', 'idx_venta_detalle_cita');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_detalle');
    }
};
