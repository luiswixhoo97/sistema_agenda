<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Agregar campos necesarios para el sistema
            $table->foreignId('role_id')->after('id')->constrained('roles');
            $table->string('telefono', 20)->nullable()->after('name');
            $table->boolean('active')->default(true)->after('remember_token');
            $table->softDeletes();
            
            // Renombrar name a nombre
            $table->renameColumn('name', 'nombre');
            
            // Índices
            $table->index('role_id', 'idx_users_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex('idx_users_role');
            $table->dropColumn(['role_id', 'telefono', 'active']);
            $table->dropSoftDeletes();
            $table->renameColumn('nombre', 'name');
        });
    }
};

