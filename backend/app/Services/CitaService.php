<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Servicio;
use App\Models\Promocion;
use App\Models\Notificacion;
use App\Models\Auditoria;
use App\Services\NotificacionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CitaService
{
    public function __construct(
        private DisponibilidadService $disponibilidadService,
        private NotificacionService $notificacionService
    ) {}

    /**
     * Agendar una nueva cita
     */
    public function agendar(array $datos, int $clienteId, bool $ignorarAnticipacionMinima = false, bool $enviarNotificacion = true): array
    {
        // Validar datos requeridos
        $empleadoId = $datos['empleado_id'];
        $servicioIds = $datos['servicios'];
        $fechaHora = $datos['fecha_hora'];
        $promocionId = isset($datos['promocion_id']) && $datos['promocion_id'] !== null && $datos['promocion_id'] !== '' 
            ? (int)$datos['promocion_id'] 
            : null;
        $notas = $datos['notas'] ?? null;
        
        Log::info('🔍 CitaService->agendar recibido', [
            'promocion_id' => $promocionId,
            'empleado_id' => $empleadoId,
            'servicios' => $servicioIds,
            'datos_promocion_id_raw' => $datos['promocion_id'] ?? 'no definido',
        ]);

        // Verificar disponibilidad (con bloqueo para evitar race condition)
        $disponibilidad = $this->disponibilidadService->verificarDisponibilidad(
            $empleadoId,
            $fechaHora,
            $servicioIds,
            $ignorarAnticipacionMinima
        );

        if (!$disponibilidad['disponible']) {
            return [
                'success' => false,
                'message' => $disponibilidad['mensaje'],
            ];
        }

        // Calcular duración y precio total para la auditoría/respuesta
        $duracionTotal = $this->disponibilidadService->calcularDuracionTotal($servicioIds);
        
        // ✅ VALIDAR PROMOCIÓN si se proporciona
        $promocion = null;
        if ($promocionId && $promocionId > 0) {
            Log::info('🔍 Buscando promoción', ['promocion_id' => $promocionId]);
            
            $promocion = Promocion::find($promocionId);
            
            if (!$promocion) {
                Log::warning('⚠️ Promoción no encontrada', ['promocion_id' => $promocionId]);
                return [
                    'success' => false,
                    'message' => 'Promoción no encontrada',
                ];
            }
            
            Log::info('✅ Promoción encontrada', [
                'promocion_id' => $promocion->id,
                'nombre' => $promocion->nombre,
                'descuento_porcentaje' => $promocion->descuento_porcentaje,
                'descuento_fijo' => $promocion->descuento_fijo,
            ]);
            
            // Validar que la promoción sea válida
            if (!$this->promocionEsValida($promocion, $servicioIds)) {
                Log::warning('⚠️ Promoción no válida', ['promocion_id' => $promocionId]);
                return [
                    'success' => false,
                    'message' => 'La promoción no es válida o ha expirado',
                ];
            }
            
            Log::info('✅ Promoción validada correctamente', ['promocion_id' => $promocionId]);
        } else {
            Log::info('ℹ️ No hay promoción para esta cita', ['promocion_id' => $promocionId]);
        }
        
        try {
            DB::beginTransaction();

            // Generar token único para QR compartido
            $tokenQr = $this->generarTokenQr();
            $citasCreadas = [];
            $currentTime = Carbon::parse($fechaHora);

            $servicios = Servicio::whereIn('id', $servicioIds)->get();

            // Paso 1: Calcular precios base de todos los servicios
            $preciosBase = [];
            foreach ($servicioIds as $servicioId) {
                $servicio = $servicios->find($servicioId);
                if (!$servicio) continue;

                // Calcular precio base (verificar precio especial del empleado)
                $precioBase = $servicio->empleados()
                    ->where('empleado_id', $empleadoId)
                    ->first()
                    ?->pivot
                    ?->precio_especial ?? $servicio->precio;
                
                $preciosBase[$servicioId] = $precioBase;
            }

            // Paso 2: Calcular descuentos según tipo de promoción
            $descuentosAplicados = [];
            if ($promocion) {
                if ($promocion->descuento_porcentaje) {
                    // Descuento porcentual: aplicar el mismo porcentaje a cada servicio
                    foreach ($preciosBase as $servicioId => $precioBase) {
                        $descuento = $precioBase * ($promocion->descuento_porcentaje / 100);
                        $descuentosAplicados[$servicioId] = $descuento;
                    }
                } elseif ($promocion->descuento_fijo) {
                    // Descuento fijo: distribuir proporcionalmente según precio de cada servicio
                    $precioTotal = array_sum($preciosBase);
                    if ($precioTotal > 0) {
                        foreach ($preciosBase as $servicioId => $precioBase) {
                            $proporcion = $precioBase / $precioTotal;
                            $descuento = $promocion->descuento_fijo * $proporcion;
                            $descuentosAplicados[$servicioId] = $descuento;
                        }
                    }
                }
            }

            // Paso 3: Crear citas con precios finales calculados
            foreach ($servicioIds as $servicioId) {
                $servicio = $servicios->find($servicioId);
                if (!$servicio) continue;

                $precioBase = $preciosBase[$servicioId] ?? $servicio->precio;
                $descuentoAplicado = $descuentosAplicados[$servicioId] ?? 0;
                $precioFinal = round($precioBase - $descuentoAplicado, 2);

                if ($promocion) {
                    Log::info('Aplicando descuento de promoción', [
                        'promocion_id' => $promocion->id,
                        'servicio_id' => $servicioId,
                        'precio_base' => $precioBase,
                        'descuento' => $descuentoAplicado,
                        'precio_final' => $precioFinal,
                        'tipo_descuento' => $promocion->descuento_porcentaje ? 'porcentual' : 'fijo',
                    ]);
                }

                $cita = Cita::create([
                    'cliente_id' => $clienteId,
                    'empleado_id' => $empleadoId,
                    'servicio_id' => $servicioId,
                    'promocion_id' => $promocionId,  // ✅ GUARDAR PROMOCIÓN!
                    'fecha_hora' => $currentTime->format('Y-m-d H:i:s'),
                    'duracion_total' => $servicio->duracion,
                    'estado' => Cita::ESTADO_CONFIRMADA,
                    'token_qr' => $tokenQr,
                    'precio_final' => $precioFinal,
                    'metodo_pago' => 'pendiente',
                    'notas' => $notas,
                ]);

                Auditoria::registrar('crear', 'citas', $cita->id, null, $cita->toArray());
                $citasCreadas[] = $cita;
                $currentTime->addMinutes($servicio->duracion);
            }
            
            // ✅ INCREMENTAR CONTADOR DE USOS de la promoción
            if ($promocion) {
                $promocion->incrementarUso();
                Log::info('Incrementado contador de usos de promoción', [
                    'promocion_id' => $promocion->id,
                    'usos_actuales' => $promocion->usos_actuales,
                    'usos_maximos' => $promocion->usos_maximos,
                ]);
            }

            DB::commit();

            $primeraCita = $citasCreadas[0];
            $primeraCita->load(['cliente', 'empleado.user', 'servicio']);

            if ($enviarNotificacion) {
                try {
                    $this->notificacionService->notificarCitaAgendada($primeraCita);
                } catch (\Exception $e) {
                    Log::error('Error enviando notificación: ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => 'Cita(s) agendada(s) exitosamente',
                'cita' => $this->formatearCita($primeraCita),
                'total_citas' => count($citasCreadas),
                'token_qr' => $tokenQr
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al agendar citas: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Error al agendar las citas. Por favor, intenta de nuevo.',
                'debug' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ];
        }
    }

    /**
     * Modificar una cita existente
     */
    public function modificar(int $citaId, array $datos, int $clienteId): array
    {
        $cita = Cita::where('id', $citaId)
            ->where('cliente_id', $clienteId)
            ->first();

        if (!$cita) {
            return [
                'success' => false,
                'message' => 'Cita no encontrada',
            ];
        }

        if (!$cita->puedeModificarse()) {
            return [
                'success' => false,
                'message' => 'Esta cita no puede ser modificada',
            ];
        }

        // Si cambia fecha/hora, verificar disponibilidad
        if (isset($datos['fecha_hora']) && $datos['fecha_hora'] !== $cita->fecha_hora->format('Y-m-d H:i:s')) {
            $servicioIds = isset($datos['servicios']) 
                ? $datos['servicios'] 
                : [$cita->servicio_id];

            $disponibilidad = $this->disponibilidadService->verificarDisponibilidad(
                $datos['empleado_id'] ?? $cita->empleado_id,
                $datos['fecha_hora'],
                $servicioIds
            );

            if (!$disponibilidad['disponible']) {
                return [
                    'success' => false,
                    'message' => $disponibilidad['mensaje'],
                ];
            }
        }

        try {
            DB::beginTransaction();

            $datosAnteriores = $cita->toArray();

            // Actualizar campos permitidos
            $camposActualizables = ['fecha_hora', 'notas'];
            $actualizaciones = array_intersect_key($datos, array_flip($camposActualizables));

            if (!empty($actualizaciones)) {
                // Recalcular duración si cambia fecha/hora
                if (isset($actualizaciones['fecha_hora'])) {
                    $servicioIds = isset($datos['servicios']) ? $datos['servicios'] : [$cita->servicio_id];
                    $actualizaciones['duracion_total'] = $this->disponibilidadService->calcularDuracionTotal($servicioIds);
                }

                $cita->update($actualizaciones);
            }

            // Registrar auditoría
            Auditoria::registrar(
                'modificar',
                'citas',
                $cita->id,
                $datosAnteriores,
                $cita->fresh()->toArray()
            );

            DB::commit();

            $cita->load(['cliente', 'empleado.user', 'servicio']);

            // Encolar notificaciones
            $this->encolarNotificaciones($cita, 'modificacion');

            return [
                'success' => true,
                'message' => 'Cita modificada exitosamente',
                'cita' => $this->formatearCita($cita),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al modificar cita: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al modificar la cita',
            ];
        }
    }

    /**
     * Cancelar una cita
     */
    public function cancelar(int $citaId, int $clienteId, ?string $motivo = null): array
    {
        $cita = Cita::where('id', $citaId)
            ->where('cliente_id', $clienteId)
            ->first();

        if (!$cita) {
            return [
                'success' => false,
                'message' => 'Cita no encontrada',
            ];
        }

        if (!$cita->puedeCancelarse()) {
            return [
                'success' => false,
                'message' => 'Esta cita no puede ser cancelada',
            ];
        }

        try {
            DB::beginTransaction();

            $datosAnteriores = $cita->toArray();

            $cita->update([
                'estado' => Cita::ESTADO_CANCELADA,
                'notas' => $cita->notas . ($motivo ? "\n[Cancelación: {$motivo}]" : ''),
            ]);

            // Registrar auditoría
            Auditoria::registrar(
                'cancelar',
                'citas',
                $cita->id,
                $datosAnteriores,
                $cita->fresh()->toArray()
            );

            DB::commit();

            $cita->load(['cliente', 'empleado.user']);

            // Encolar notificaciones
            $this->encolarNotificaciones($cita, 'cancelacion');

            return [
                'success' => true,
                'message' => 'Cita cancelada exitosamente',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al cancelar cita: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al cancelar la cita',
            ];
        }
    }

    /**
     * Cambiar estado de una cita (para empleados/admin)
     */
    public function cambiarEstado(int $citaId, string $nuevoEstado, ?int $empleadoId = null): array
    {
        $query = Cita::where('id', $citaId);
        
        // Si es empleado, solo puede cambiar sus propias citas
        if ($empleadoId) {
            $query->where('empleado_id', $empleadoId);
        }

        $cita = $query->first();

        if (!$cita) {
            return [
                'success' => false,
                'message' => 'Cita no encontrada',
            ];
        }

        // Validar transición de estado
        $transicionesValidas = [
            Cita::ESTADO_CONFIRMADA => [Cita::ESTADO_COMPLETADA, Cita::ESTADO_CANCELADA, Cita::ESTADO_REAGENDADA],
            Cita::ESTADO_REAGENDADA => [Cita::ESTADO_COMPLETADA, Cita::ESTADO_CANCELADA, Cita::ESTADO_REAGENDADA], // Se puede reagendar de nuevo
            Cita::ESTADO_COMPLETADA => [], // Estado final, no se puede cambiar
            Cita::ESTADO_CANCELADA => [], // Estado final, no se puede cambiar
        ];

        if (!isset($transicionesValidas[$cita->estado]) || 
            !in_array($nuevoEstado, $transicionesValidas[$cita->estado])) {
            return [
                'success' => false,
                'message' => "No se puede cambiar de '{$cita->estado}' a '{$nuevoEstado}'",
            ];
        }

        try {
            DB::beginTransaction();

            $datosAnteriores = $cita->toArray();

            $cita->update(['estado' => $nuevoEstado]);

            // Registrar auditoría
            Auditoria::registrar(
                'cambiar_estado',
                'citas',
                $cita->id,
                $datosAnteriores,
                $cita->fresh()->toArray()
            );

            DB::commit();

            // Notificar según el nuevo estado
            $citaActualizada = $cita->fresh()->load(['cliente', 'empleado.user', 'servicio']);
            
            if ($nuevoEstado === Cita::ESTADO_CONFIRMADA) {
                $this->encolarNotificaciones($citaActualizada, 'confirmacion');
            } elseif ($nuevoEstado === Cita::ESTADO_CANCELADA) {
                $this->encolarNotificaciones($citaActualizada, 'cancelacion');
            }

            return [
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'estado' => $nuevoEstado,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al cambiar estado: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al cambiar el estado',
            ];
        }
    }

    /**
     * Reagendar una cita (para empleados/admin)
     * Marca la cita original como "reagendada" y crea una nueva cita con la nueva fecha
     */
    public function reagendar(int $citaId, string $nuevaFechaHora, ?string $motivo = null, ?int $empleadoId = null): array
    {
        Log::info('Reagendar cita', [
            'citaId' => $citaId,
            'nuevaFechaHora' => $nuevaFechaHora,
            'motivo' => $motivo,
            'empleadoId' => $empleadoId,
        ]);

        $query = Cita::with(['cliente', 'empleado', 'servicio'])->where('id', $citaId);
        
        // Si es empleado, solo puede reagendar sus propias citas
        if ($empleadoId) {
            $query->where('empleado_id', $empleadoId);
        }

        $citaOriginal = $query->first();

        Log::info('Cita encontrada', ['citaOriginal' => $citaOriginal ? $citaOriginal->toArray() : null]);

        if (!$citaOriginal) {
            Log::warning('Cita no encontrada para reagendar', ['citaId' => $citaId, 'empleadoId' => $empleadoId]);
            return [
                'success' => false,
                'message' => 'Cita no encontrada o no tienes permisos para reagendarla',
            ];
        }

        // Se pueden reagendar citas confirmadas o reagendadas
        if (!in_array($citaOriginal->estado, [Cita::ESTADO_CONFIRMADA, Cita::ESTADO_REAGENDADA])) {
            return [
                'success' => false,
                'message' => 'Solo se pueden reagendar citas confirmadas o reagendadas',
            ];
        }

        // Obtener ID de servicio
        $servicioIds = [$citaOriginal->servicio_id];

        // Verificar disponibilidad en la nueva fecha/hora
        Log::info('Verificando disponibilidad', [
            'empleado_id' => $citaOriginal->empleado_id,
            'nuevaFechaHora' => $nuevaFechaHora,
            'servicioIds' => $servicioIds,
        ]);

        $disponibilidad = $this->disponibilidadService->verificarDisponibilidad(
            $citaOriginal->empleado_id,
            $nuevaFechaHora,
            $servicioIds,
            true, // Ignorar anticipación mínima para empleados/admin
            null, // tokenReservaExcluir
            $citaOriginal->id // Excluir la cita que se está reagendando
        );

        Log::info('Resultado disponibilidad', $disponibilidad);

        if (!$disponibilidad['disponible']) {
            return [
                'success' => false,
                'message' => $disponibilidad['mensaje'] ?? 'El horario seleccionado no está disponible',
            ];
        }

        try {
            DB::beginTransaction();

            $datosOriginales = $citaOriginal->toArray();

            // 1. Marcar la cita original como "reagendada"
            $notasActualizadas = $citaOriginal->notas ?? '';
            $notasActualizadas .= "\n[Reagendada el " . now()->format('d/m/Y H:i') . "]";
            if ($motivo) {
                $notasActualizadas .= " Motivo: {$motivo}";
            }

            $citaOriginal->update([
                'estado' => Cita::ESTADO_REAGENDADA,
                'notas' => trim($notasActualizadas),
            ]);

            // 2. Crear la nueva cita con los mismos datos
            // Verificar si la cita original es parte de un grupo coordinado (comparte token_qr con otras citas)
            $citasCoordinadas = Cita::where('token_qr', $citaOriginal->token_qr)
                ->where('id', '!=', $citaOriginal->id)
                ->whereNull('deleted_at')
                ->count();
            
            // Si hay otras citas con el mismo token_qr, mantener el mismo token para el grupo
            // Si no, generar un nuevo token (cita individual)
            $tokenQrParaNuevaCita = ($citasCoordinadas > 0) 
                ? $citaOriginal->token_qr  // Mantener el mismo token del grupo coordinado
                : $this->generarTokenQr(); // Generar nuevo token para cita individual
            
            $nuevaCita = Cita::create([
                'cliente_id' => $citaOriginal->cliente_id,
                'empleado_id' => $citaOriginal->empleado_id,
                'servicio_id' => $citaOriginal->servicio_id,
                'promocion_id' => $citaOriginal->promocion_id,
                'fecha_hora' => $nuevaFechaHora,
                'duracion_total' => $citaOriginal->duracion_total,
                'estado' => Cita::ESTADO_CONFIRMADA, // La nueva cita queda confirmada
                'token_qr' => $tokenQrParaNuevaCita, // Mantener token del grupo si es coordinada, o nuevo si es individual
                'precio_final' => $citaOriginal->precio_final,
                'metodo_pago' => $citaOriginal->metodo_pago,
                'notas' => "Reagendada desde cita #{$citaOriginal->id}" . ($motivo ? " - Motivo: {$motivo}" : ''),
            ]);

            // 4. Registrar auditoría para la cita original
            Auditoria::registrar(
                'reagendar',
                'citas',
                $citaOriginal->id,
                $datosOriginales,
                array_merge($citaOriginal->fresh()->toArray(), ['nueva_cita_id' => $nuevaCita->id])
            );

            // 5. Registrar auditoría para la nueva cita
            Auditoria::registrar(
                'crear_reagendamiento',
                'citas',
                $nuevaCita->id,
                null,
                array_merge($nuevaCita->toArray(), ['cita_original_id' => $citaOriginal->id])
            );

            DB::commit();

            // Cargar relaciones de la nueva cita
            $nuevaCita->load(['cliente', 'empleado.user', 'servicio']);

            // Encolar notificaciones de reagendamiento
            $this->encolarNotificaciones($nuevaCita, 'reagendamiento');

            return [
                'success' => true,
                'message' => 'Cita reagendada exitosamente',
                'cita_original_id' => $citaOriginal->id,
                'nueva_cita' => $this->formatearCita($nuevaCita),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al reagendar cita: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Error al reagendar la cita',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    /**
     * Verificar si una promoción es válida para los servicios
     */
    public function promocionEsValida(Promocion $promocion, array $servicioIds): bool
    {
        // ✅ Verificar que esté vigente
        if (!$promocion->estaVigente()) {
            Log::info('Promoción no vigente', [
                'promocion_id' => $promocion->id,
                'fecha_inicio' => $promocion->fecha_inicio,
                'fecha_fin' => $promocion->fecha_fin,
                'hoy' => now()->toDateString(),
            ]);
            return false;
        }

        // ✅ Verificar que tenga usos disponibles
        if (!$promocion->tieneUsosDisponibles()) {
            Log::info('Promoción sin usos disponibles', [
                'promocion_id' => $promocion->id,
                'usos_actuales' => $promocion->usos_actuales,
                'usos_maximos' => $promocion->usos_maximos,
            ]);
            return false;
        }

        // ✅ Verificar que la promoción esté activa
        if (!$promocion->active) {
            Log::info('Promoción inactiva', ['promocion_id' => $promocion->id]);
            return false;
        }

        // ✅ Verificar que aplica a TODOS los servicios seleccionados
        if ($promocion->servicios_aplicables !== null && !empty($promocion->servicios_aplicables)) {
            // La promoción solo aplica a servicios específicos
            // Verificar que TODOS los servicios seleccionados estén en la lista
            foreach ($servicioIds as $servicioId) {
                if (!in_array($servicioId, $promocion->servicios_aplicables)) {
                    Log::info('Servicio no incluido en promoción', [
                        'promocion_id' => $promocion->id,
                        'servicio_id' => $servicioId,
                        'servicios_aplicables' => $promocion->servicios_aplicables,
                        'servicios_seleccionados' => $servicioIds,
                    ]);
                    return false;
                }
            }
        }

        // ✅ Promoción válida
        Log::info('Promoción validada correctamente', [
            'promocion_id' => $promocion->id,
            'nombre' => $promocion->nombre,
            'servicios' => $servicioIds,
        ]);

        return true;
    }

    /**
     * Encolar notificaciones para la cita
     * Llama al NotificacionService para enviar las notificaciones reales
     */
    private function encolarNotificaciones(Cita $cita, string $tipo): void
    {
        try {
            // Cargar relaciones necesarias
            $cita->load(['cliente', 'empleado.user', 'servicio']);
            
            switch ($tipo) {
                case 'cancelacion':
                    // Extraer motivo de las notas si existe
                    $motivo = '';
                    if ($cita->notas && preg_match('/\[Cancelación: (.+?)\]/', $cita->notas, $matches)) {
                        $motivo = $matches[1];
                    }
                    $this->notificacionService->notificarCitaCancelada($cita, $motivo);
                    break;
                    
                case 'reagendamiento':
                case 'modificacion':
                    // Enviar notificación de modificación con nuevo QR
                    $this->notificacionService->notificarCitaModificada($cita);
                    break;
                    
                case 'confirmacion':
                    $this->notificacionService->notificarCitaAgendada($cita);
                    break;
                    
                default:
                    Log::warning("Tipo de notificación no reconocido: {$tipo}", ['cita_id' => $cita->id]);
            }
            
            Log::info("✅ Notificación encolada correctamente", [
                'cita_id' => $cita->id,
                'tipo' => $tipo,
            ]);
            
        } catch (\Exception $e) {
            // Log del error pero no fallar la operación principal
            Log::error("Error encolando notificación de {$tipo}: " . $e->getMessage(), [
                'cita_id' => $cita->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generar token único para QR de la cita
     */
    protected function generarTokenQr(): string
    {
        do {
            $token = bin2hex(random_bytes(32)); // Token de 64 caracteres
        } while (Cita::where('token_qr', $token)->exists());

        return $token;
    }

    /**
     * Formatear cita para respuesta API
     */
    public function formatearCita(Cita $cita): array
    {
        // Cargar promoción si existe
        $promocionInfo = null;
        if ($cita->promocion_id) {
            $promocion = $cita->promocion ?? Promocion::find($cita->promocion_id);
            if ($promocion) {
                $promocionInfo = [
                    'id' => $promocion->id,
                    'nombre' => $promocion->nombre,
                    'descuento' => $promocion->descuento_porcentaje 
                        ? "{$promocion->descuento_porcentaje}%" 
                        : "\${$promocion->descuento_fijo}",
                    'descuento_porcentaje' => $promocion->descuento_porcentaje,
                    'descuento_fijo' => $promocion->descuento_fijo,
                ];
            }
        }

        return [
            'id' => $cita->id,
            'fecha_hora' => $cita->fecha_hora->format('Y-m-d H:i'),
            'fecha' => $cita->fecha_hora->format('Y-m-d'),
            'hora' => $cita->fecha_hora->format('H:i'),
            'fecha_texto' => $cita->fecha_hora->format('d/m/Y'),
            'hora_texto' => $cita->fecha_hora->format('h:i A'),
            'duracion_total' => $cita->duracion_total,
            'estado' => $cita->estado,
            'precio_final' => $cita->precio_final,
            'metodo_pago' => $cita->metodo_pago,
            'notas' => $cita->notas,
            'cliente' => $cita->cliente ? [
                'id' => $cita->cliente->id,
                'nombre' => $cita->cliente->nombre,
                'telefono' => $cita->cliente->telefono,
            ] : null,
            'empleado' => $cita->empleado ? [
                'id' => $cita->empleado->id,
                'nombre' => $cita->empleado->user->nombre,
                'foto' => $cita->empleado->foto,
            ] : null,
            'servicios' => $cita->servicio ? [[
                'id' => $cita->servicio->id,
                'nombre' => $cita->servicio->nombre,
                'precio_aplicado' => $cita->precio_final,
                'duracion' => $cita->servicio->duracion,
            ]] : [],
            'promocion' => $promocionInfo,
            'puede_cancelarse' => $cita->puedeCancelarse(),
            'puede_modificarse' => $cita->puedeModificarse(),
        ];
    }
}

