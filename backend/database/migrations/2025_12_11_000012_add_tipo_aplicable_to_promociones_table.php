<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promociones', function (Blueprint $table) {
            // Agregar tipo aplicable
            $table->enum('tipo_aplicable', ['servicios', 'productos', 'ambos'])->default('servicios')->after('descripcion');
            
            // Agregar productos aplicables (similar a servicios_aplicables)
            $table->json('productos_aplicables')->nullable()->after('servicios_aplicables')->comment('array de producto_ids, NULL = todos los productos');
        });
        
        // Agregar índice
        Schema::table('promociones', function (Blueprint $table) {
            $table->index('tipo_aplicable', 'idx_promociones_tipo_aplicable');
        });
    }

    public function down(): void
    {
        Schema::table('promociones', function (Blueprint $table) {
            $table->dropIndex('idx_promociones_tipo_aplicable');
            $table->dropColumn(['tipo_aplicable', 'productos_aplicables']);
        });
    }
};
