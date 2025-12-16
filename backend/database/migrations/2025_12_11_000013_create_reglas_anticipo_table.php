<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_anticipo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->enum('tipo_regla', ['fecha', 'monto', 'servicio']);
            $table->enum('tipo_calculo', ['porcentaje', 'monto_fijo']);
            $table->decimal('valor_calculo', 10, 2)->comment('Porcentaje (0-100) o monto fijo según tipo_calculo');
            $table->integer('prioridad')->default(0)->comment('Mayor número = mayor prioridad');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index('tipo_regla', 'idx_reglas_anticipo_tipo');
            $table->index('activo', 'idx_reglas_anticipo_activo');
            $table->index('prioridad', 'idx_reglas_anticipo_prioridad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_anticipo');
    }
};
