<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->integer('descuento_porcentaje')->nullable();
            $table->decimal('descuento_fijo', 10, 2)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->json('servicios_aplicables')->nullable()->comment('array de servicio_ids, NULL = todos');
            $table->integer('usos_maximos')->nullable()->comment('NULL = ilimitado');
            $table->integer('usos_actuales')->default(0);
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['fecha_inicio', 'fecha_fin'], 'idx_promociones_fechas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};

