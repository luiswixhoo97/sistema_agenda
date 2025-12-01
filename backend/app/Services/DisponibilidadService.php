<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\HorarioEmpleado;
use App\Models\BloqueoTiempo;
use App\Models\Cita;
use App\Models\Servicio;
use App\Models\Configuracion;
use App\Models\DiaFestivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DisponibilidadService
{
    // Configuraciones cacheadas
    private ?string $horarioApertura = null;
    private ?string $horarioCierre = null;
    private ?int $duracionSlot = null;
    private ?int $anticipacionMinima = null;
    private ?int $anticipacionMaxima = null;

    public function __construct()
    {
        $this->cargarConfiguracion();
    }

    /**
     * Cargar configuraciones del sistema
     */
    private function cargarConfiguracion(): void
    {
        $this->horarioApertura = Configuracion::horarioApertura();
        $this->horarioCierre = Configuracion::horarioCierre();
        $this->duracionSlot = Configuracion::duracionSlot();
        $this->anticipacionMinima = Configuracion::anticipacionMinima();
        $this->anticipacionMaxima = Configuracion::anticipacionMaxima();
    }

    /**
     * Obtener días con disponibilidad en un mes
     * 
     * @param int $empleadoId
     * @param int $mes (1-12)
     * @param int $anio
     * @param array $servicioIds
     * @return array
     */
    public function obtenerDiasConDisponibilidad(
        int $empleadoId,
        int $mes,
        int $anio,
        array $servicioIds
    ): array {
        $empleado = Empleado::with('horarios')->findOrFail($empleadoId);
        $duracionTotal = $this->calcularDuracionTotal($servicioIds);
        
        // Obtener rango de fechas del mes
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes = $inicioMes->copy()->endOfMonth();
        
        // Ajustar inicio si es el mes actual
        $hoy = Carbon::now();
        if ($inicioMes->lt($hoy)) {
            $inicioMes = $hoy->copy()->startOfDay();
        }
        
        // Verificar anticipación máxima
        $fechaMaxima = $hoy->copy()->addDays($this->anticipacionMaxima);
        if ($finMes->gt($fechaMaxima)) {
            $finMes = $fechaMaxima;
        }
        
        // Obtener días festivos del mes
        $diasFestivos = DiaFestivo::whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->where('aplica_a_todos', true)
            ->pluck('fecha')
            ->map(fn($f) => Carbon::parse($f)->format('Y-m-d'))
            ->toArray();
        
        // Obtener horarios del empleado indexados por día
        $horariosEmpleado = $empleado->horarios
            ->where('active', true)
            ->keyBy('dia_semana');
        
        $dias = [];
        $fechaActual = $inicioMes->copy();
        
        while ($fechaActual->lte($finMes)) {
            $fechaStr = $fechaActual->format('Y-m-d');
            $diaSemana = $fechaActual->dayOfWeekIso; // 1=Lunes, 7=Domingo
            
            // Verificar si es día festivo
            $esFestivo = in_array($fechaStr, $diasFestivos);
            
            // Verificar si el empleado trabaja ese día
            $tieneHorario = isset($horariosEmpleado[$diaSemana]);
            
            // Por ahora marcamos si podría tener disponibilidad
            // (no calculamos slots individuales para performance)
            $tieneDisponibilidad = !$esFestivo && $tieneHorario;
            
            $dias[] = [
                'fecha' => $fechaStr,
                'dia_semana' => $diaSemana,
                'tiene_disponibilidad' => $tieneDisponibilidad,
                'es_festivo' => $esFestivo,
            ];
            
            $fechaActual->addDay();
        }
        
        return $dias;
    }

    /**
     * Obtener slots disponibles para una fecha específica
     * 
     * @param int $empleadoId
     * @param string $fecha (Y-m-d)
     * @param array $servicioIds
     * @return array
     */
    public function obtenerSlotsDisponibles(
        int $empleadoId,
        string $fecha,
        array $servicioIds,
        bool $ignorarAnticipacionMinima = false
    ): array {
        $fechaCarbon = Carbon::parse($fecha);
        $duracionTotal = $this->calcularDuracionTotal($servicioIds);
        
        // Validar fecha
        $validacion = $this->validarFecha($fechaCarbon);
        if (!$validacion['valida']) {
            return [
                'fecha' => $fecha,
                'empleado_id' => $empleadoId,
                'duracion_total' => $duracionTotal,
                'slots' => [],
                'mensaje' => $validacion['mensaje'],
            ];
        }
        
        // Verificar si es día festivo
        if (DiaFestivo::esFestivo($fecha)) {
            return [
                'fecha' => $fecha,
                'empleado_id' => $empleadoId,
                'duracion_total' => $duracionTotal,
                'slots' => [],
                'mensaje' => 'El negocio está cerrado por día festivo',
            ];
        }
        
        // Obtener horario del empleado para este día
        $diaSemana = $fechaCarbon->dayOfWeekIso;
        $horario = HorarioEmpleado::where('empleado_id', $empleadoId)
            ->where('dia_semana', $diaSemana)
            ->where('active', true)
            ->first();
        
        if (!$horario) {
            return [
                'fecha' => $fecha,
                'empleado_id' => $empleadoId,
                'duracion_total' => $duracionTotal,
                'slots' => [],
                'mensaje' => 'El empleado no trabaja este día',
            ];
        }
        
        \Log::info('Horario del empleado', [
            'empleado_id' => $empleadoId,
            'fecha' => $fecha,
            'dia_semana' => $diaSemana,
            'hora_inicio' => $horario->hora_inicio,
            'hora_fin' => $horario->hora_fin,
        ]);
        
        // Obtener bloqueos del día
        $bloqueos = BloqueoTiempo::where('fecha', $fecha)
            ->where(function ($q) use ($empleadoId) {
                $q->where('empleado_id', $empleadoId)
                  ->orWhereNull('empleado_id');
            })
            ->get()
            ->map(fn($b) => [
                'inicio' => $b->hora_inicio,
                'fin' => $b->hora_fin,
            ])
            ->toArray();
        
        // Obtener citas del día
        $citas = Cita::where('empleado_id', $empleadoId)
            ->whereDate('fecha_hora', $fecha)
            ->whereNotIn('estado', ['cancelada', 'no_show'])
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($cita) {
                $inicio = Carbon::parse($cita->fecha_hora);
                $fin = $inicio->copy()->addMinutes($cita->duracion_total);
                return [
                    'inicio' => $inicio->format('H:i'),
                    'fin' => $fin->format('H:i'),
                ];
            })
            ->toArray();
        
        // Generar slots
        $slots = $this->generarSlots(
            $horario->hora_inicio,
            $horario->hora_fin,
            $duracionTotal,
            $bloqueos,
            $citas,
            $fechaCarbon,
            $ignorarAnticipacionMinima
        );
        
        return [
            'fecha' => $fecha,
            'empleado_id' => $empleadoId,
            'duracion_total' => $duracionTotal,
            'slots' => $slots,
            'horario_empleado' => [
                'hora_inicio' => $horario->hora_inicio,
                'hora_fin' => $horario->hora_fin,
            ],
            'bloqueos_count' => count($bloqueos),
            'citas_count' => count($citas),
            'mensaje' => null,
        ];
    }

    /**
     * Verificar disponibilidad específica
     * 
     * @param int $empleadoId
     * @param string $fechaHora (Y-m-d H:i:s)
     * @param array $servicioIds
     * @return array
     */
    public function verificarDisponibilidad(
        int $empleadoId,
        string $fechaHora,
        array $servicioIds
    ): array {
        $fechaHoraCarbon = Carbon::parse($fechaHora);
        $fecha = $fechaHoraCarbon->format('Y-m-d');
        $hora = $fechaHoraCarbon->format('H:i');
        $duracionTotal = $this->calcularDuracionTotal($servicioIds);
        $horaFin = $fechaHoraCarbon->copy()->addMinutes($duracionTotal)->format('H:i');
        
        // Validar fecha
        $validacion = $this->validarFecha($fechaHoraCarbon);
        if (!$validacion['valida']) {
            return [
                'disponible' => false,
                'duracion_total' => $duracionTotal,
                'hora_fin' => $horaFin,
                'mensaje' => $validacion['mensaje'],
            ];
        }
        
        // Verificar anticipación mínima
        $minimoPermitido = Carbon::now()->addHours($this->anticipacionMinima);
        if ($fechaHoraCarbon->lt($minimoPermitido)) {
            return [
                'disponible' => false,
                'duracion_total' => $duracionTotal,
                'hora_fin' => $horaFin,
                'mensaje' => "Debe agendar con al menos {$this->anticipacionMinima} horas de anticipación",
            ];
        }
        
        // Verificar día festivo
        if (DiaFestivo::esFestivo($fecha)) {
            return [
                'disponible' => false,
                'duracion_total' => $duracionTotal,
                'hora_fin' => $horaFin,
                'mensaje' => 'El negocio está cerrado por día festivo',
            ];
        }
        
        // Verificar horario del empleado
        $diaSemana = $fechaHoraCarbon->dayOfWeekIso;
        $horario = HorarioEmpleado::where('empleado_id', $empleadoId)
            ->where('dia_semana', $diaSemana)
            ->where('active', true)
            ->first();
        
        if (!$horario) {
            return [
                'disponible' => false,
                'duracion_total' => $duracionTotal,
                'hora_fin' => $horaFin,
                'mensaje' => 'El empleado no trabaja este día',
            ];
        }
        
        // Verificar que el slot esté dentro del horario del empleado
        if ($hora < $horario->hora_inicio || $horaFin > $horario->hora_fin) {
            return [
                'disponible' => false,
                'duracion_total' => $duracionTotal,
                'hora_fin' => $horaFin,
                'mensaje' => 'El horario está fuera del horario de trabajo del empleado',
            ];
        }
        
        // Verificar bloqueos
        $hayBloqueo = BloqueoTiempo::where('fecha', $fecha)
            ->where(function ($q) use ($empleadoId) {
                $q->where('empleado_id', $empleadoId)
                  ->orWhereNull('empleado_id');
            })
            ->get()
            ->contains(function ($bloqueo) use ($hora, $horaFin) {
                return $this->haySolapamiento($hora, $horaFin, $bloqueo->hora_inicio, $bloqueo->hora_fin);
            });
        
        if ($hayBloqueo) {
            return [
                'disponible' => false,
                'duracion_total' => $duracionTotal,
                'hora_fin' => $horaFin,
                'mensaje' => 'El horario está bloqueado',
            ];
        }
        
        // Verificar citas existentes
        $hayCita = Cita::where('empleado_id', $empleadoId)
            ->whereDate('fecha_hora', $fecha)
            ->whereNotIn('estado', ['cancelada', 'no_show'])
            ->whereNull('deleted_at')
            ->get()
            ->contains(function ($cita) use ($hora, $horaFin) {
                $citaInicio = Carbon::parse($cita->fecha_hora)->format('H:i');
                $citaFin = Carbon::parse($cita->fecha_hora)->addMinutes($cita->duracion_total)->format('H:i');
                return $this->haySolapamiento($hora, $horaFin, $citaInicio, $citaFin);
            });
        
        if ($hayCita) {
            return [
                'disponible' => false,
                'duracion_total' => $duracionTotal,
                'hora_fin' => $horaFin,
                'mensaje' => 'Ya existe una cita en este horario',
            ];
        }
        
        return [
            'disponible' => true,
            'duracion_total' => $duracionTotal,
            'hora_fin' => $horaFin,
            'mensaje' => null,
        ];
    }

    /**
     * Calcular duración total de servicios
     */
    public function calcularDuracionTotal(array $servicioIds): int
    {
        if (empty($servicioIds)) {
            return 0;
        }
        
        return Servicio::whereIn('id', $servicioIds)
            ->where('active', true)
            ->sum('duracion');
    }

    /**
     * Calcular precio total de servicios
     */
    public function calcularPrecioTotal(array $servicioIds, ?int $empleadoId = null): float
    {
        if (empty($servicioIds)) {
            return 0;
        }
        
        $servicios = Servicio::whereIn('id', $servicioIds)
            ->where('active', true)
            ->get();
        
        $total = 0;
        
        foreach ($servicios as $servicio) {
            // Verificar si el empleado tiene precio especial
            if ($empleadoId) {
                $precioEspecial = $servicio->empleados()
                    ->where('empleado_id', $empleadoId)
                    ->first()
                    ?->pivot
                    ?->precio_especial;
                
                $total += $precioEspecial ?? $servicio->precio;
            } else {
                $total += $servicio->precio;
            }
        }
        
        return $total;
    }

    /**
     * Validar fecha
     */
    private function validarFecha(Carbon $fecha): array
    {
        $hoy = Carbon::now()->startOfDay();
        
        // No en el pasado
        if ($fecha->lt($hoy)) {
            return [
                'valida' => false,
                'mensaje' => 'No se puede agendar en fechas pasadas',
            ];
        }
        
        // Dentro de anticipación máxima
        $fechaMaxima = $hoy->copy()->addDays($this->anticipacionMaxima);
        if ($fecha->gt($fechaMaxima)) {
            return [
                'valida' => false,
                'mensaje' => "Solo se puede agendar hasta {$this->anticipacionMaxima} días de anticipación",
            ];
        }
        
        return ['valida' => true, 'mensaje' => null];
    }

    /**
     * Generar slots disponibles
     */
    private function generarSlots(
        string $horaInicio,
        string $horaFin,
        int $duracionTotal,
        array $bloqueos,
        array $citas,
        Carbon $fecha,
        bool $ignorarAnticipacionMinima = false
    ): array {
        $slots = [];
        $ahora = Carbon::now();
        $esHoy = $fecha->isToday();
        
        // Convertir horas a minutos desde medianoche para cálculos
        $inicioMinutos = $this->horaAMinutos($horaInicio);
        $finMinutos = $this->horaAMinutos($horaFin);
        
        $slotActual = $inicioMinutos;
        
        while ($slotActual + $duracionTotal <= $finMinutos) {
            $horaSlot = $this->minutosAHora($slotActual);
            $horaFinSlot = $this->minutosAHora($slotActual + $duracionTotal);
            
            $disponible = true;
            
            // Si es hoy, verificar que no sea en el pasado + anticipación mínima
            // (a menos que se ignore la anticipación mínima, como cuando un empleado crea su propia cita)
            if ($esHoy && !$ignorarAnticipacionMinima) {
                $slotDateTime = $fecha->copy()->setTimeFromTimeString($horaSlot . ':00');
                $minimoPermitido = $ahora->copy()->addHours($this->anticipacionMinima);
                
                if ($slotDateTime->lt($minimoPermitido)) {
                    $disponible = false;
                }
            } elseif ($esHoy && $ignorarAnticipacionMinima) {
                // Si se ignora la anticipación mínima, solo verificar que no sea en el pasado
                $slotDateTime = $fecha->copy()->setTimeFromTimeString($horaSlot . ':00');
                if ($slotDateTime->lt($ahora)) {
                    $disponible = false;
                }
            }
            
            // Log para depuración
            if ($esHoy && $ignorarAnticipacionMinima) {
                \Log::debug('Slot generado', [
                    'hora' => $horaSlot,
                    'es_hoy' => $esHoy,
                    'ignorar_anticipacion' => $ignorarAnticipacionMinima,
                    'ahora' => $ahora->format('H:i'),
                    'slot_datetime' => $esHoy ? $fecha->copy()->setTimeFromTimeString($horaSlot . ':00')->format('Y-m-d H:i') : null,
                    'disponible' => $disponible
                ]);
            }
            
            // Verificar bloqueos
            if ($disponible) {
                foreach ($bloqueos as $bloqueo) {
                    if ($this->haySolapamiento($horaSlot, $horaFinSlot, $bloqueo['inicio'], $bloqueo['fin'])) {
                        $disponible = false;
                        break;
                    }
                }
            }
            
            // Verificar citas existentes
            if ($disponible) {
                foreach ($citas as $cita) {
                    if ($this->haySolapamiento($horaSlot, $horaFinSlot, $cita['inicio'], $cita['fin'])) {
                        $disponible = false;
                        break;
                    }
                }
            }
            
            if ($disponible) {
                $slots[] = [
                    'hora' => $horaSlot,
                    'hora_fin' => $horaFinSlot,
                ];
            }
            
            $slotActual += $this->duracionSlot;
        }
        
        return $slots;
    }

    /**
     * Verificar solapamiento entre dos rangos de tiempo
     */
    private function haySolapamiento(
        string $inicio1,
        string $fin1,
        string $inicio2,
        string $fin2
    ): bool {
        $i1 = $this->horaAMinutos($inicio1);
        $f1 = $this->horaAMinutos($fin1);
        $i2 = $this->horaAMinutos($inicio2);
        $f2 = $this->horaAMinutos($fin2);
        
        return $i1 < $f2 && $f1 > $i2;
    }

    /**
     * Convertir hora (HH:mm) a minutos desde medianoche
     */
    private function horaAMinutos(string $hora): int
    {
        $partes = explode(':', $hora);
        return (int)$partes[0] * 60 + (int)$partes[1];
    }

    /**
     * Convertir minutos desde medianoche a hora (HH:mm)
     */
    private function minutosAHora(int $minutos): string
    {
        $horas = floor($minutos / 60);
        $mins = $minutos % 60;
        return sprintf('%02d:%02d', $horas, $mins);
    }

    /**
     * Obtener empleados disponibles para un servicio en una fecha/hora
     */
    public function obtenerEmpleadosDisponibles(
        array $servicioIds,
        string $fechaHora
    ): Collection {
        $fechaHoraCarbon = Carbon::parse($fechaHora);
        $fecha = $fechaHoraCarbon->format('Y-m-d');
        $diaSemana = $fechaHoraCarbon->dayOfWeekIso;
        
        // Obtener empleados que ofrecen todos los servicios
        $empleados = Empleado::with(['user', 'horarios', 'servicios'])
            ->where('active', true)
            ->whereHas('user', fn($q) => $q->where('active', true))
            ->get()
            ->filter(function ($empleado) use ($servicioIds) {
                // Verificar que ofrece todos los servicios
                $serviciosEmpleado = $empleado->servicios->pluck('id')->toArray();
                return empty(array_diff($servicioIds, $serviciosEmpleado));
            })
            ->filter(function ($empleado) use ($diaSemana) {
                // Verificar que trabaja ese día
                return $empleado->horarios
                    ->where('dia_semana', $diaSemana)
                    ->where('active', true)
                    ->isNotEmpty();
            })
            ->filter(function ($empleado) use ($fechaHora, $servicioIds) {
                // Verificar disponibilidad real
                $disponibilidad = $this->verificarDisponibilidad(
                    $empleado->id,
                    $fechaHora,
                    $servicioIds
                );
                return $disponibilidad['disponible'];
            });
        
        return $empleados->values();
    }
}

