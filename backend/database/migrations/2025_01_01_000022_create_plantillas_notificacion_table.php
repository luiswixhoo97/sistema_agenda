<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_notificacion', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['nueva_cita', 'confirmacion', 'recordatorio', 'recordatorio_dia', 'cancelacion', 'modificacion', 'promocion', 'otp']);
            $table->enum('medio', ['email', 'whatsapp', 'push']);
            $table->string('asunto', 255)->nullable()->comment('para email');
            $table->text('contenido');
            $table->json('variables')->nullable()->comment('lista de variables disponibles');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['tipo', 'medio'], 'unique_tipo_medio');
        });

        // Insertar plantillas por defecto
        $plantillas = [
            // WhatsApp
            ['tipo' => 'nueva_cita', 'medio' => 'whatsapp', 'asunto' => null, 'contenido' => "¡Hola {{cliente_nombre}}! 👋\n\nTu cita ha sido agendada:\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicio: {{servicios}}\n👤 Con: {{empleado_nombre}}\n\n¡Te esperamos!", 'variables' => json_encode(['cliente_nombre', 'fecha', 'hora', 'servicios', 'empleado_nombre'])],
            ['tipo' => 'recordatorio', 'medio' => 'whatsapp', 'asunto' => null, 'contenido' => "¡Hola {{cliente_nombre}}! 👋\n\nTe recordamos tu cita para mañana:\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicio: {{servicios}}\n\n¡Te esperamos! 😊\n\nResponde CANCELAR si no podrás asistir.", 'variables' => json_encode(['cliente_nombre', 'fecha', 'hora', 'servicios'])],
            ['tipo' => 'recordatorio_dia', 'medio' => 'whatsapp', 'asunto' => null, 'contenido' => "¡Hola {{cliente_nombre}}! 👋\n\nTu cita es en 2 horas:\n⏰ {{hora}}\n💇 {{servicios}}\n\n¡Te esperamos pronto! 🎉", 'variables' => json_encode(['cliente_nombre', 'hora', 'servicios'])],
            ['tipo' => 'cancelacion', 'medio' => 'whatsapp', 'asunto' => null, 'contenido' => "Hola {{cliente_nombre}},\n\nTu cita del {{fecha}} a las {{hora}} ha sido cancelada.\n\nSi deseas reagendar, puedes hacerlo desde nuestra app.\n\nDisculpa los inconvenientes.", 'variables' => json_encode(['cliente_nombre', 'fecha', 'hora'])],
            ['tipo' => 'otp', 'medio' => 'whatsapp', 'asunto' => null, 'contenido' => "Tu código de verificación es: {{codigo}}\n\nEste código expira en 5 minutos.\nNo compartas este código con nadie.", 'variables' => json_encode(['codigo'])],
            
            // Push
            ['tipo' => 'nueva_cita', 'medio' => 'push', 'asunto' => 'Cita Confirmada ✅', 'contenido' => 'Tu cita para {{servicios}} el {{fecha}} a las {{hora}} ha sido confirmada.', 'variables' => json_encode(['servicios', 'fecha', 'hora'])],
            ['tipo' => 'recordatorio', 'medio' => 'push', 'asunto' => 'Recordatorio de Cita 📅', 'contenido' => 'Mañana tienes cita a las {{hora}} para {{servicios}}', 'variables' => json_encode(['hora', 'servicios'])],
            ['tipo' => 'recordatorio_dia', 'medio' => 'push', 'asunto' => '¡Tu cita es pronto! ⏰', 'contenido' => 'Tu cita es en 2 horas. {{servicios}} a las {{hora}}', 'variables' => json_encode(['servicios', 'hora'])],
            ['tipo' => 'cancelacion', 'medio' => 'push', 'asunto' => 'Cita Cancelada', 'contenido' => 'Tu cita del {{fecha}} ha sido cancelada.', 'variables' => json_encode(['fecha'])],
        ];

        foreach ($plantillas as $plantilla) {
            $plantilla['activo'] = true;
            $plantilla['created_at'] = now();
            $plantilla['updated_at'] = now();
            DB::table('plantillas_notificacion')->insert($plantilla);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_notificacion');
    }
};

