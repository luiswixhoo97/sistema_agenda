<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('tipo', 20)->default('login')->after('telefono');
            
            // Cambiar índice para incluir tipo
            $table->dropIndex('idx_otp_telefono');
            $table->index(['telefono', 'tipo'], 'idx_otp_telefono_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex('idx_otp_telefono_tipo');
            $table->index('telefono', 'idx_otp_telefono');
            $table->dropColumn('tipo');
        });
    }
};
