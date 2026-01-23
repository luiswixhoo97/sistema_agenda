<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->boolean('requiere_anticipo')->default(false)->after('precio_final');
            $table->decimal('monto_anticipo_requerido', 10, 2)->default(0)->after('requiere_anticipo');
            $table->foreignId('venta_id')->nullable()->after('monto_anticipo_requerido')->constrained('ventas')->onDelete('set null');
            
            $table->index('venta_id', 'idx_citas_venta');
            $table->index(['requiere_anticipo', 'venta_id'], 'idx_citas_anticipo_venta');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['venta_id']);
            $table->dropIndex('idx_citas_venta');
            $table->dropIndex('idx_citas_anticipo_venta');
            $table->dropColumn(['requiere_anticipo', 'monto_anticipo_requerido', 'venta_id']);
        });
    }
};
