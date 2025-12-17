<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class DatosBancariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Datos bancarios por defecto
        $datosBancarios = [
            [
                'clave' => 'banco_nombre',
                'valor' => 'Banco Ejemplo',
                'tipo' => 'string',
                'descripcion' => 'Nombre del banco para transferencias de anticipo',
            ],
            [
                'clave' => 'banco_numero_cuenta',
                'valor' => '1234567890',
                'tipo' => 'string',
                'descripcion' => 'Número de cuenta bancaria para transferencias de anticipo',
            ],
            [
                'clave' => 'banco_clabe',
                'valor' => '012345678901234567',
                'tipo' => 'string',
                'descripcion' => 'CLABE interbancaria para transferencias (opcional)',
            ],
            [
                'clave' => 'banco_titular',
                'valor' => 'Nombre del Negocio',
                'tipo' => 'string',
                'descripcion' => 'Nombre del titular de la cuenta bancaria',
            ],
        ];

        foreach ($datosBancarios as $dato) {
            Configuracion::updateOrCreate(
                ['clave' => $dato['clave']],
                [
                    'valor' => $dato['valor'],
                    'tipo' => $dato['tipo'],
                    'descripcion' => $dato['descripcion'],
                ]
            );
        }

        $this->command->info('✅ Datos bancarios sembrados correctamente');
        $this->command->info('   - banco_nombre: ' . Configuracion::get('banco_nombre'));
        $this->command->info('   - banco_numero_cuenta: ' . Configuracion::get('banco_numero_cuenta'));
        $this->command->info('   - banco_clabe: ' . (Configuracion::get('banco_clabe') ?: 'No configurado'));
        $this->command->info('   - banco_titular: ' . Configuracion::get('banco_titular'));
    }
}
