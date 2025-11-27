<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->text('valor');
            $table->string('tipo', 50)->default('string')->comment('string, int, float, json, boolean');
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // Insertar configuraciones por defecto
        DB::table('configuracion')->insert([
            ['clave' => 'horario_apertura', 'valor' => '09:00', 'tipo' => 'string', 'descripcion' => 'Hora de apertura del negocio', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'horario_cierre', 'valor' => '20:00', 'tipo' => 'string', 'descripcion' => 'Hora de cierre del negocio', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'duracion_slot_minutos', 'valor' => '30', 'tipo' => 'int', 'descripcion' => 'Duración de cada slot de tiempo en minutos', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'anticipacion_minima_horas', 'valor' => '2', 'tipo' => 'int', 'descripcion' => 'Horas mínimas de anticipación para agendar', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'dias_anticipacion_maxima', 'valor' => '60', 'tipo' => 'int', 'descripcion' => 'Días máximos de anticipación para agendar', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'recordatorio_horas_antes', 'valor' => '24', 'tipo' => 'int', 'descripcion' => 'Horas antes de la cita para enviar recordatorio', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'recordatorio_dia_horas_antes', 'valor' => '2', 'tipo' => 'int', 'descripcion' => 'Horas antes de la cita para recordatorio del día', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'nombre_negocio', 'valor' => 'Estética', 'tipo' => 'string', 'descripcion' => 'Nombre del negocio', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'direccion_negocio', 'valor' => '', 'tipo' => 'string', 'descripcion' => 'Dirección del negocio', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'telefono_negocio', 'valor' => '', 'tipo' => 'string', 'descripcion' => 'Teléfono del negocio', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};

