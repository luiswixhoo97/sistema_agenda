<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dias_festivos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('nombre', 100);
            $table->boolean('aplica_a_todos')->default(true)->comment('si es feriado para todo el negocio');
            $table->timestamps();
        });

        // Insertar días festivos de México (ejemplo para 2025)
        $festivos = [
            ['fecha' => '2025-01-01', 'nombre' => 'Año Nuevo'],
            ['fecha' => '2025-02-03', 'nombre' => 'Día de la Constitución'],
            ['fecha' => '2025-03-17', 'nombre' => 'Natalicio de Benito Juárez'],
            ['fecha' => '2025-05-01', 'nombre' => 'Día del Trabajo'],
            ['fecha' => '2025-09-16', 'nombre' => 'Día de la Independencia'],
            ['fecha' => '2025-11-17', 'nombre' => 'Revolución Mexicana'],
            ['fecha' => '2025-12-25', 'nombre' => 'Navidad'],
        ];

        foreach ($festivos as $festivo) {
            $festivo['aplica_a_todos'] = true;
            $festivo['created_at'] = now();
            $festivo['updated_at'] = now();
            DB::table('dias_festivos')->insert($festivo);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dias_festivos');
    }
};

