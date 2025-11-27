<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotos_citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->onDelete('cascade');
            $table->enum('tipo', ['antes', 'despues', 'proceso']);
            $table->string('ruta_archivo', 255);
            $table->text('descripcion')->nullable();
            $table->timestamps();
            
            $table->index('cita_id', 'idx_fotos_cita');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos_citas');
    }
};

