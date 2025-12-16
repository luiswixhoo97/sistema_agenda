<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('codigo', 50)->unique();
            $table->boolean('es_efectivo')->default(false);
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
            
            $table->index('codigo', 'idx_metodos_pago_codigo');
            $table->index('activo', 'idx_metodos_pago_activo');
        });

        // Insertar métodos de pago iniciales
        DB::table('metodos_pago')->insert([
            [
                'nombre' => 'Efectivo',
                'codigo' => 'efectivo',
                'es_efectivo' => true,
                'activo' => true,
                'orden' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Tarjeta de Débito',
                'codigo' => 'tarjeta_debito',
                'es_efectivo' => false,
                'activo' => true,
                'orden' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Tarjeta de Crédito',
                'codigo' => 'tarjeta_credito',
                'es_efectivo' => false,
                'activo' => true,
                'orden' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Transferencia',
                'codigo' => 'transferencia',
                'es_efectivo' => false,
                'activo' => true,
                'orden' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Mercado Pago',
                'codigo' => 'mercado_pago',
                'es_efectivo' => false,
                'activo' => true,
                'orden' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Stripe',
                'codigo' => 'stripe',
                'es_efectivo' => false,
                'activo' => true,
                'orden' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos_pago');
    }
};
