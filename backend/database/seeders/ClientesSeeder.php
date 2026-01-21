<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClientesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // El primer cliente debe ser Público General para ventas de mostrador
        Cliente::firstOrCreate(
            ['telefono' => '0000000000'],
            [
                'nombre' => 'Público General',
                'active' => true
            ]
        );
    }
}
