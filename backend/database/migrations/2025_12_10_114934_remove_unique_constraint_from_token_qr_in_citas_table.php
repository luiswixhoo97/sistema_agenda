<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Elimina la restricción UNIQUE del campo token_qr para permitir
     * que múltiples citas coordinadas compartan el mismo token QR.
     */
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // Eliminar el índice único existente
            $table->dropUnique(['token_qr']);
        });
        
        // Asegurarse de que el índice normal existe para búsquedas rápidas
        // (sin restricción UNIQUE)
        if (!$this->indexExists('citas', 'idx_citas_token_qr')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->index('token_qr', 'idx_citas_token_qr');
            });
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Restaura la restricción UNIQUE (aunque esto podría causar problemas
     * si ya existen citas coordinadas con el mismo token_qr)
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // Eliminar el índice normal primero
            $table->dropIndex('idx_citas_token_qr');
            
            // Restaurar la restricción UNIQUE
            $table->unique('token_qr');
        });
    }

    /**
     * Verificar si un índice existe
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$databaseName, $table, $index]
        );
        
        return $result[0]->count > 0;
    }
};
