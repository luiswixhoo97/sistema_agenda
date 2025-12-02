<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modificar el ENUM para agregar 'reagendada'
        DB::statement("ALTER TABLE citas MODIFY COLUMN estado ENUM('pendiente', 'confirmada', 'en_proceso', 'completada', 'cancelada', 'no_show', 'reagendada') DEFAULT 'pendiente'");
    }

    public function down(): void
    {
        // Revertir al ENUM original (las citas con estado 'reagendada' deberán manejarse antes)
        DB::statement("ALTER TABLE citas MODIFY COLUMN estado ENUM('pendiente', 'confirmada', 'en_proceso', 'completada', 'cancelada', 'no_show') DEFAULT 'pendiente'");
    }
};

