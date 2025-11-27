<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('foto', 255)->nullable();
            $table->text('bio')->nullable();
            $table->text('especialidades')->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('user_id', 'idx_empleados_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};

