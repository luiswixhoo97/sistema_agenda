<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\CitaServicio;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\Servicio;
use App\Models\Promocion;
use App\Models\Notificacion;
use App\Models\Auditoria;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CitaService
{
    public function __construct(
        private DisponibilidadService $disponibilidadService
    ) {}

    /**
     * Agendar una nueva cita
     */
    public function agendar(array $datos, int $clienteId, bool $ignorarAnticipacionMinima = false): array
    {
        // Validar datos requeridos
        $empleadoId = $datos['empleado_id'];
        $servicioIds = $datos['servicios'];
        $fechaHora = $datos['fecha_hora'];
        $promocionId = $datos['promocion_id'] ?? null;
        $notas = $datos['notas'] ?? null;

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

        // Calcular duración y precio
        $duracionTotal = $this->disponibilidadService->calcularDuracionTotal($servicioIds);
        $precioBase = $this->disponibilidadService->calcularPrecioTotal($servicioIds, $empleadoId);
        
        // Aplicar promoción si existe
        $descuento = 0;
        if ($promocionId) {
            $promocion = Promocion::find($promocionId);
            if ($promocion && $this->promocionEsValida($promocion, $servicioIds)) {
                $descuento = $promocion->calcularDescuento($precioBase);
            } else {
                $promocionId = null; // Invalidar promoción no válida
            }
        }
        
        $precioFinal = $precioBase - $descuento;

        try {
            DB::beginTransaction();

            // Crear la cita
            $cita = Cita::create([
                'cliente_id' => $clienteId,
                'empleado_id' => $empleadoId,
                'servicio_id' => $servicioIds[0], // Servicio principal
                'promocion_id' => $promocionId,
                'fecha_hora' => $fechaHora,
                'duracion_total' => $duracionTotal,
                'estado' => Cita::ESTADO_PENDIENTE,
                'precio_final' => $precioFinal,
                'metodo_pago' => 'pendiente',
                'notas' => $notas,
            ]);

            // Crear registros de servicios de la cita
            $servicios = Servicio::whereIn('id', $servicioIds)->get();
            $orden = 1;
            
            foreach ($servicioIds as $servicioId) {
                $servicio = $servicios->find($servicioId);
                if ($servicio) {
                    // Obtener precio (especial del empleado o estándar)
                    $precioAplicado = $servicio->empleados()
                        ->where('empleado_id', $empleadoId)
                        ->first()
                        ?->pivot
                        ?->precio_especial ?? $servicio->precio;
                    
                    CitaServicio::create([
                        'cita_id' => $cita->id,
                        'servicio_id' => $servicioId,
                        'precio_aplicado' => $precioAplicado,
                        'orden' => $orden++,
                    ]);
                }
            }

            // Incrementar uso de promoción
            if ($promocionId) {
                Promocion::find($promocionId)->incrementarUso();
            }

            // Registrar auditoría
            Auditoria::registrar(
                'crear',
                'citas',
                $cita->id,
                null,
                $cita->toArray()
            );

            DB::commit();

            // Cargar relaciones
            $cita->load(['cliente', 'empleado.user', 'servicio', 'servicios.servicio']);

            // Encolar notificaciones (asíncrono)
            $this->encolarNotificaciones($cita, 'nueva_cita');

            return [
                'success' => true,
                'message' => 'Cita agendada exitosamente',
                'cita' => $this->formatearCita($cita),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al agendar cita: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Error al agendar la cita. Por favor, intenta de nuevo.',
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
                : $cita->servicios->pluck('servicio_id')->toArray();

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
                    $servicioIds = $cita->servicios->pluck('servicio_id')->toArray();
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

            $cita->load(['cliente', 'empleado.user', 'servicio', 'servicios.servicio']);

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
            Cita::ESTADO_PENDIENTE => [Cita::ESTADO_CONFIRMADA, Cita::ESTADO_CANCELADA],
            Cita::ESTADO_CONFIRMADA => [Cita::ESTADO_EN_PROCESO, Cita::ESTADO_CANCELADA, Cita::ESTADO_NO_SHOW],
            Cita::ESTADO_EN_PROCESO => [Cita::ESTADO_COMPLETADA],
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
            if ($nuevoEstado === Cita::ESTADO_CONFIRMADA) {
                $this->encolarNotificaciones($cita->fresh()->load(['cliente', 'empleado.user']), 'confirmacion');
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
     * Verificar si una promoción es válida para los servicios
     */
    private function promocionEsValida(Promocion $promocion, array $servicioIds): bool
    {
        if (!$promocion->estaVigente()) {
            return false;
        }

        if (!$promocion->tieneUsosDisponibles()) {
            return false;
        }

        // Verificar que aplica a al menos uno de los servicios
        foreach ($servicioIds as $servicioId) {
            if ($promocion->aplicaAServicio($servicioId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Encolar notificaciones para la cita
     */
    private function encolarNotificaciones(Cita $cita, string $tipo): void
    {
        $cliente = $cita->cliente;

        // Crear registros de notificación según preferencias del cliente
        $medios = [];
        
        if ($cliente->notificaciones_whatsapp) {
            $medios[] = 'whatsapp';
        }
        if ($cliente->notificaciones_email && $cliente->email) {
            $medios[] = 'email';
        }
        if ($cliente->notificaciones_push) {
            $medios[] = 'push';
        }

        foreach ($medios as $medio) {
            Notificacion::create([
                'cita_id' => $cita->id,
                'cliente_id' => $cliente->id,
                'tipo' => $tipo,
                'medio' => $medio,
                'estado' => 'pendiente',
            ]);
        }

        // TODO: Encolar job para procesar notificaciones
        // dispatch(new ProcesarNotificacionesJob($cita->id, $tipo));
    }

    /**
     * Formatear cita para respuesta API
     */
    public function formatearCita(Cita $cita): array
    {
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
            'servicios' => $cita->servicios->map(fn($cs) => [
                'id' => $cs->servicio->id,
                'nombre' => $cs->servicio->nombre,
                'precio_aplicado' => $cs->precio_aplicado,
                'duracion' => $cs->servicio->duracion,
            ])->toArray(),
            'puede_cancelarse' => $cita->puedeCancelarse(),
            'puede_modificarse' => $cita->puedeModificarse(),
        ];
    }
}

