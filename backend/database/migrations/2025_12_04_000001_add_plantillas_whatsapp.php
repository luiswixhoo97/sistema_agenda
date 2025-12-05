<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Primero, mapear los tipos existentes a los nuevos
        $mappings = [
            'confirmacion' => 'confirmacion_cita',
            'recordatorio' => 'recordatorio_cita',
            'cancelacion' => 'cancelacion_cita',
            'modificacion' => 'modificacion_cita',
        ];

        // Solo para MySQL: cambiar a VARCHAR temporalmente para actualizar datos
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // Cambiar a VARCHAR para permitir la actualización
            DB::statement("ALTER TABLE plantillas_notificacion MODIFY COLUMN tipo VARCHAR(50) NOT NULL");
        }

        // Actualizar los tipos existentes
        foreach ($mappings as $old => $new) {
            DB::table('plantillas_notificacion')
                ->where('tipo', $old)
                ->update(['tipo' => $new]);
        }

        // Volver a ENUM con todos los tipos (incluyendo recordatorio_dia)
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE plantillas_notificacion MODIFY COLUMN tipo ENUM('nueva_cita', 'confirmacion_cita', 'recordatorio_cita', 'recordatorio_dia', 'cancelacion_cita', 'modificacion_cita', 'promocion', 'otp') NOT NULL");
        }

        // Insertar/actualizar plantillas de WhatsApp con los tipos correctos
        $plantillas = [
            [
                'tipo' => 'confirmacion_cita',
                'medio' => 'whatsapp',
                'asunto' => null,
                'contenido' => "¡Hola {{cliente_nombre}}! 👋\n\n✅ Tu cita ha sido confirmada:\n\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicios: {{servicios}}\n👤 Empleado: {{empleado_nombre}}\n💰 Precio: \${{precio_total}}\n⏱️ Duración: {{duracion_total}} minutos\n\n📍 {{negocio_nombre}}\n{{negocio_direccion}}\n\n¡Te esperamos! 😊",
                'variables' => json_encode(['cliente_nombre', 'fecha', 'hora', 'servicios', 'empleado_nombre', 'precio_total', 'duracion_total', 'negocio_nombre', 'negocio_direccion']),
            ],
            [
                'tipo' => 'modificacion_cita',
                'medio' => 'whatsapp',
                'asunto' => null,
                'contenido' => "¡Hola {{cliente_nombre}}! 👋\n\n📝 Tu cita ha sido reagendada:\n\n📅 Nueva fecha: {{fecha}}\n⏰ Nueva hora: {{hora}}\n💇 Servicios: {{servicios}}\n👤 Empleado: {{empleado_nombre}}\n💰 Precio: \${{precio_total}}\n⏱️ Duración: {{duracion_total}} minutos\n\n📍 {{negocio_nombre}}\n{{negocio_direccion}}\n\n¡Te esperamos! 😊",
                'variables' => json_encode(['cliente_nombre', 'fecha', 'hora', 'servicios', 'empleado_nombre', 'precio_total', 'duracion_total', 'negocio_nombre', 'negocio_direccion']),
            ],
            [
                'tipo' => 'cancelacion_cita',
                'medio' => 'whatsapp',
                'asunto' => null,
                'contenido' => "Hola {{cliente_nombre}} 👋\n\n❌ Tu cita ha sido cancelada:\n\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicios: {{servicios}}\n\n{{motivo_cancelacion}}\n\nPuedes agendar una nueva cita en cualquier momento. ¡Esperamos verte pronto! 😊",
                'variables' => json_encode(['cliente_nombre', 'fecha', 'hora', 'servicios', 'motivo_cancelacion']),
            ],
            [
                'tipo' => 'recordatorio_cita',
                'medio' => 'whatsapp',
                'asunto' => null,
                'contenido' => "¡Hola {{cliente_nombre}}! 👋\n\n⏰ Te recordamos tu cita para mañana:\n\n📅 Fecha: {{fecha}}\n⏰ Hora: {{hora}}\n💇 Servicios: {{servicios}}\n👤 Empleado: {{empleado_nombre}}\n\n📍 {{negocio_nombre}}\n{{negocio_direccion}}\n\n¡Te esperamos! 😊",
                'variables' => json_encode(['cliente_nombre', 'fecha', 'hora', 'servicios', 'empleado_nombre', 'negocio_nombre', 'negocio_direccion']),
            ],
            [
                'tipo' => 'otp',
                'medio' => 'whatsapp',
                'asunto' => null,
                'contenido' => "🔐 *Código de verificación*\n\nTu código es: *{{codigo_otp}}*\n\n⏱️ Válido por {{expiracion_minutos}} minutos.\n\n⚠️ No compartas este código con nadie.",
                'variables' => json_encode(['codigo_otp', 'expiracion_minutos']),
            ],
        ];

        foreach ($plantillas as $plantilla) {
            // Verificar si ya existe
            $existe = DB::table('plantillas_notificacion')
                ->where('tipo', $plantilla['tipo'])
                ->where('medio', $plantilla['medio'])
                ->exists();

            if ($existe) {
                // Actualizar contenido
                DB::table('plantillas_notificacion')
                    ->where('tipo', $plantilla['tipo'])
                    ->where('medio', $plantilla['medio'])
                    ->update([
                        'contenido' => $plantilla['contenido'],
                        'variables' => $plantilla['variables'],
                        'updated_at' => now(),
                    ]);
            } else {
                // Insertar nueva
                $plantilla['activo'] = true;
                $plantilla['created_at'] = now();
                $plantilla['updated_at'] = now();
                DB::table('plantillas_notificacion')->insert($plantilla);
            }
        }
    }

    public function down(): void
    {
        // Revertir los tipos a los originales
        $mappings = [
            'confirmacion_cita' => 'confirmacion',
            'recordatorio_cita' => 'recordatorio',
            'cancelacion_cita' => 'cancelacion',
            'modificacion_cita' => 'modificacion',
        ];

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE plantillas_notificacion MODIFY COLUMN tipo VARCHAR(50) NOT NULL");
        }

        foreach ($mappings as $new => $old) {
            DB::table('plantillas_notificacion')
                ->where('tipo', $new)
                ->update(['tipo' => $old]);
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE plantillas_notificacion MODIFY COLUMN tipo ENUM('nueva_cita', 'confirmacion', 'recordatorio', 'recordatorio_dia', 'cancelacion', 'modificacion', 'promocion', 'otp') NOT NULL");
        }
    }
};
