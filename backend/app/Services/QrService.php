<?php

namespace App\Services;

use App\Models\Cita;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class QrService
{
    /**
     * Generar código QR para una cita
     * 
     * @param Cita $cita
     * @return string|null URL del QR (data URI en formato PNG) o null si hay error
     */
    public function generarQrCita(Cita $cita): ?string
    {
        try {
            // Asegurar que la cita tenga un token QR
            if (!$cita->token_qr) {
                Log::warning("Cita sin token QR, no se puede generar código", [
                    'cita_id' => $cita->id,
                ]);
                return null;
            }

            // Generar URL para escanear el QR
            // El token se puede escanear desde la app del empleado/admin
            $urlQr = $this->generarUrlQr($cita->token_qr);

            // Generar QR code como PNG (base64 data URI)
            // Usar formato PNG porque WhatsApp no acepta SVG
            // Nota: Requiere ext-imagick para generar PNG
            try {
                $qrPng = QrCode::format('png')
                    ->size(300)
                    ->margin(2)
                    ->errorCorrection('H') // Alto nivel de corrección de errores
                    ->generate($urlQr);

                // Convertir a data URI
                $dataUri = 'data:image/png;base64,' . base64_encode($qrPng);
            } catch (\Exception $e) {
                // Si falla PNG (probablemente falta imagick), intentar SVG y convertir
                Log::warning("No se pudo generar PNG, intentando SVG: " . $e->getMessage());
                
                // Generar como SVG y convertir a PNG usando GD (si está disponible)
                // Por ahora, retornar null si no se puede generar PNG
                // El sistema funcionará sin QR en este caso
                return null;
            }

            Log::info("✅ QR generado para cita", [
                'cita_id' => $cita->id,
                'token_qr' => substr($cita->token_qr, 0, 10) . '...',
                'qr_size' => strlen($qrPng),
            ]);

            return $dataUri;

        } catch (\Exception $e) {
            Log::error("Error al generar QR para cita: " . $e->getMessage(), [
                'cita_id' => $cita->id ?? null,
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);
            
            return null;
        }
    }

    /**
     * Procesar escaneo de QR
     * 
     * @param string $token Token del QR
     * @param \App\Models\User $user Usuario que escanea
     * @return array Resultado de la operación
     */
    public function procesarEscaneo(string $token, \App\Models\User $user): array
    {
        // 1. Validar permisos básicos
        $user->load('role', 'empleado');
        $esAdmin = $user->isAdmin();
        $esEmpleado = $user->isEmpleado() && $user->empleado;

        if (!$esEmpleado && !$esAdmin) {
            return [
                'success' => false,
                'message' => 'No tienes permisos para escanear QR.',
                'code' => 403
            ];
        }

        // 2. Buscar citas
        $citas = Cita::where('token_qr', $token)
            ->whereNull('deleted_at')
            ->get();

        if ($citas->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Código QR no válido o cita no encontrada.',
                'code' => 404
            ];
        }

        // 3. Validar estados y actualizar a completada
        // Si es empleado, solo puede "marcar" su cita, pero necesitamos verificar/crear venta para TODO el grupo
        // La lógica de negocio dice: "tiene que marcar todas las citas del token como completadas, pero validara..."
        // Asumiremos que el escaneo implica completar el servicio.

        $citasACompletar = $citas->filter(function ($cita) {
            return in_array($cita->estado, [Cita::ESTADO_CONFIRMADA, Cita::ESTADO_REAGENDADA]);
        });

        // Si ya están todas completadas, pasamos directo a la venta.
        // Si hay algunas confirmadas, las completamos.
        
        $citasActualizadas = 0;
        foreach ($citasACompletar as $cita) {
            // Si es empleado, validar que sea SU cita (o permitir si es un flow de caja?)
            // El usuario pidió: "marcar todas las citas del token como completadas"
            // Siendo estricto con los permisos de empleado:
            if ($esEmpleado && !$esAdmin && $cita->empleado_id !== $user->empleado->id) {
                // Si el empleado escanea un grupo donde hay citas de otros, 
                // ¿debería marcar todas? Asumiremos que SI, si están coordinadas por el mismo token.
                // O quizás solo marca la suya y verificamos si todas están listas.
                // Dado el requerimiento "marcar TODAS", asumiremos que el QR es "master" para el grupo.
                // Pero mantenemos logs por seguridad.
                Log::info("Empleado {$user->id} completando cita {$cita->id} asignada a otro empleado {$cita->empleado_id} via QR coordinado");
            }

            $cita->estado = Cita::ESTADO_COMPLETADA;
            $cita->save();
            $citasActualizadas++;
        }

        // 4. Generar o Recuperar Venta
        // Buscar si alguna de estas citas ya tiene un detalle de venta
        $ventaDetalle = \App\Models\VentaDetalle::whereIn('cita_id', $citas->pluck('id'))->first();
        $venta = null;

        if ($ventaDetalle) {
            $venta = $ventaDetalle->venta;
        } else {
            // Crear nueva venta
            $venta = $this->generarVentaDesdeCitas($citas, $citas[0]->cliente_id);
        }
        
        // Recargar venta con relaciones necesarias para el frontend
        if ($venta) {
            $venta->load('cliente', 'detalles.servicio', 'pagos');
            $venta->actualizarSaldo(); // Asegurar montos frescos
        }

        return [
            'success' => true,
            'message' => $citasActualizadas > 0 
                ? "Se han completado $citasActualizadas citas y preparado la venta." 
                : "Citas ya concluidas. Venta lista.",
            'citas_actualizadas' => $citasActualizadas,
            'venta' => $venta,
            'cita_principal' => $citas->first() // Para mostrar info básica
        ];
    }

    /**
     * Generar una venta a partir de un grupo de citas
     */
    protected function generarVentaDesdeCitas($citas, $clienteId): \App\Models\Venta
    {
        // Calcular totales
        $subtotal = 0;
        $total = 0;
        // Asumimos impuestos incluidos o calcúlalo según tu lógica. 
        // Usaremos precio_final de la cita como el valor a cobrar.

        $venta = \App\Models\Venta::create([
            'cliente_id' => $clienteId,
            'fecha_venta' => now(),
            'estado' => \App\Models\Venta::ESTADO_PENDIENTE_PAGO,
            'subtotal' => 0, // Se actualizará
            'impuesto_total' => 0,
            'total' => 0,
            'saldo_pendiente' => 0,
            'notas' => 'Generada automáticamente por escaneo QR de citas: ' . $citas->pluck('id')->implode(', ')
        ]);

        foreach ($citas as $cita) {
            $precio = $cita->precio_final;
            
            \App\Models\VentaDetalle::create([
                'venta_id' => $venta->id,
                'tipo' => \App\Models\VentaDetalle::TIPO_SERVICIO,
                'servicio_id' => $cita->servicio_id,
                'cita_id' => $cita->id,
                'promocion_id' => $cita->promocion_id,
                'cantidad' => 1,
                'precio_unitario' => $precio,
                'subtotal_linea' => $precio,
                'impuesto' => 0, // Ajustar si hay lógica de impuestos
                'descuento' => 0, // El precio_final ya tiene descuentos aplicados en la cita?
            ]);

            $subtotal += $precio;
            $total += $precio;
        }

        $venta->subtotal = $subtotal;
        $venta->total = $total;
        $venta->saldo_pendiente = $total;
        $venta->save();

        return $venta;
    }

    /**
     * Generar URL para el código QR
     * 
     * @param string $token
     * @return string
     */
    protected function generarUrlQr(string $token): string
    {
        // Opción 1: URL completa para escanear (requiere que la app maneje deep links)
        // $appUrl = config('app.url');
        // return "{$appUrl}/api/publico/citas/scan-qr/{$token}";

        // Opción 2: Solo el token (la app debe tener lógica para escanearlo)
        // Por ahora, retornamos solo el token ya que el endpoint de scan requiere autenticación
        // La app móvil puede manejar el token directamente
        return $token;
    }

    /**
     * Generar QR code simple (para otros usos)
     * 
     * @param string $contenido
     * @param int $tamaño
     * @return string|null Data URI del QR o null si hay error
     */
    public function generarQr(string $contenido, int $tamaño = 300): ?string
    {
        try {
            $qrPng = QrCode::format('png')
                ->size($tamaño)
                ->margin(2)
                ->errorCorrection('H')
                ->generate($contenido);

            return 'data:image/png;base64,' . base64_encode($qrPng);

        } catch (\Exception $e) {
            Log::error("Error al generar QR: " . $e->getMessage());
            return null;
        }
    }
}
