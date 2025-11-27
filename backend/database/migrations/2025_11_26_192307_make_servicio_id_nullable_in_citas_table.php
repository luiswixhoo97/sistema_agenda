<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // Eliminar foreign key primero
            $table->dropForeign(['servicio_id']);
            
            // Hacer nullable
            $table->foreignId('servicio_id')->nullable()->change();
            
            // Re-agregar foreign key (nullable)
            $table->foreign('servicio_id')->references('id')->on('servicios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['servicio_id']);
            $table->foreignId('servicio_id')->nullable(false)->change();
            $table->foreign('servicio_id')->references('id')->on('servicios');
        });
    }
};
