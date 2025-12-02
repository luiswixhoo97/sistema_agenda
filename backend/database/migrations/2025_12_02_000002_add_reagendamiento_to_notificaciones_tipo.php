<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar 'reagendamiento' al ENUM de tipo en notificaciones
        DB::statement("ALTER TABLE notificaciones MODIFY COLUMN tipo ENUM('nueva_cita', 'confirmacion', 'recordatorio', 'recordatorio_dia', 'cancelacion', 'modificacion', 'recordatorio_empleado', 'promocion', 'reagendamiento')");
    }

    public function down(): void
    {
        // Revertir al ENUM original
        DB::statement("ALTER TABLE notificaciones MODIFY COLUMN tipo ENUM('nueva_cita', 'confirmacion', 'recordatorio', 'recordatorio_dia', 'cancelacion', 'modificacion', 'recordatorio_empleado', 'promocion')");
    }
};

