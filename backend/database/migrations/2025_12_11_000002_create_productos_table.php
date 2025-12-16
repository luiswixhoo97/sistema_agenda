<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100)->unique();
            $table->string('nombre', 255);
            $table->string('foto', 255)->nullable();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias_productos')->nullOnDelete();
            $table->integer('inventario_minimo')->default(0);
            $table->integer('inventario_actual')->default(0);
            $table->decimal('costo', 10, 2)->default(0);
            $table->decimal('precio', 10, 2);
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('codigo', 'idx_productos_codigo');
            $table->index('categoria_id', 'idx_productos_categoria');
            $table->index('active', 'idx_productos_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
