<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReservaTemporal extends Model
{
    use HasFactory;

    protected $table = 'reservas_temporales';

    protected $fillable = [
        'token',
        'empleado_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'duracion_total',
        'telefono_cliente',
        'session_id',
        'servicios_ids',
        'datos_adicionales',
        'expira_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'servicios_ids' => 'array',
        'datos_adicionales' => 'array',
        'expira_at' => 'datetime',
    ];

    /**
     * Tiempo de expiración por defecto en minutos
     */
    public const TIEMPO_EXPIRACION_MINUTOS = 10;

    /**
     * Relación con empleado
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * Generar un token único para la reserva
     */
    public static function generarToken(): string
    {
        return Str::random(64);
    }

    /**
     * Crear una nueva reserva temporal
     */
    public static function reservar(array $datos): self
    {
        // Limpiar reservas expiradas primero
        self::limpiarExpiradas();

        $token = self::generarToken();
        $expiraAt = Carbon::now()->addMinutes(self::TIEMPO_EXPIRACION_MINUTOS);

        return self::create([
            'token' => $token,
            'empleado_id' => $datos['empleado_id'],
            'fecha' => $datos['fecha'],
            'hora_inicio' => $datos['hora_inicio'],
            'hora_fin' => $datos['hora_fin'],
            'duracion_total' => $datos['duracion_total'],
            'telefono_cliente' => $datos['telefono_cliente'] ?? null,
            'session_id' => $datos['session_id'] ?? null,
            'servicios_ids' => $datos['servicios_ids'] ?? [],
            'datos_adicionales' => $datos['datos_adicionales'] ?? null,
            'expira_at' => $expiraAt,
        ]);
    }

    /**
     * Verificar si un slot está reservado temporalmente
     * 
     * @param int $empleadoId
     * @param string $fecha (Y-m-d)
     * @param string $horaInicio (H:i)
     * @param string $horaFin (H:i)
     * @param string|array|null $tokensExcluir - Token(s) a excluir (para la misma sesión)
     * @return bool
     */
    public static function slotReservado(
        int $empleadoId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        $tokensExcluir = null
    ): bool {
        $query = self::where('empleado_id', $empleadoId)
            ->where('fecha', $fecha)
            ->where('expira_at', '>', Carbon::now());
        
        if ($tokensExcluir) {
            if (is_array($tokensExcluir)) {
                // Excluir múltiples tokens
                if (!empty($tokensExcluir)) {
                    $query->whereNotIn('token', $tokensExcluir);
                }
            } else {
                // Excluir un solo token
                $query->where('token', '!=', $tokensExcluir);
            }
        }

        // Verificar solapamiento de horarios
        return $query->where(function ($q) use ($horaInicio, $horaFin) {
            $q->where(function ($q2) use ($horaInicio, $horaFin) {
                // Caso 1: hora_inicio está dentro del rango
                $q2->where('hora_inicio', '<', $horaFin)
                   ->where('hora_fin', '>', $horaInicio);
            });
        })->exists();
    }

    /**
     * Obtener reserva por token
     */
    public static function obtenerPorToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where('expira_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Liberar una reserva temporal por token
     */
    public static function liberar(string $token): bool
    {
        return self::where('token', $token)->delete() > 0;
    }

    /**
     * Extender el tiempo de una reserva
     */
    public function extender(int $minutos = null): bool
    {
        $minutos = $minutos ?? self::TIEMPO_EXPIRACION_MINUTOS;
        $this->expira_at = Carbon::now()->addMinutes($minutos);
        return $this->save();
    }

    /**
     * Verificar si la reserva ha expirado
     */
    public function haExpirado(): bool
    {
        return Carbon::now()->gt($this->expira_at);
    }

    /**
     * Obtener tiempo restante en segundos
     */
    public function tiempoRestante(): int
    {
        $restante = Carbon::now()->diffInSeconds($this->expira_at, false);
        return max(0, $restante);
    }

    /**
     * Limpiar reservas expiradas
     */
    public static function limpiarExpiradas(): int
    {
        return self::where('expira_at', '<=', Carbon::now())->delete();
    }

    /**
     * Scope para reservas activas (no expiradas)
     */
    public function scopeActivas($query)
    {
        return $query->where('expira_at', '>', Carbon::now());
    }

    /**
     * Scope para reservas de un empleado en una fecha
     */
    public function scopeDeEmpleadoEnFecha($query, int $empleadoId, string $fecha)
    {
        return $query->where('empleado_id', $empleadoId)
                     ->where('fecha', $fecha);
    }
}

