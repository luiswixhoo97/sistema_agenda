<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('telefono', 20);
            $table->string('codigo', 10);
            $table->tinyInteger('intentos')->default(0);
            $table->boolean('verificado')->default(false);
            $table->timestamp('expira_at');
            $table->timestamp('created_at')->nullable();
            
            $table->index('telefono', 'idx_otp_telefono');
            $table->index('expira_at', 'idx_otp_expira');
            $table->index(['verificado', 'expira_at'], 'idx_otp_cleanup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};

