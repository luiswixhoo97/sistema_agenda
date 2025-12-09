<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Cita extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cliente_id',
        'empleado_id',
        'servicio_id',
        'promocion_id',
        'fecha_hora',
        'duracion_total',
        'estado',
        'token_qr',
        'precio_final',
        'metodo_pago',
        'notas',
        'active',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'duracion_total' => 'integer',
        'precio_final' => 'decimal:2',
        'active' => 'boolean',
    ];

    const ESTADO_CONFIRMADA = 'confirmada';
    const ESTADO_COMPLETADA = 'completada';
    const ESTADO_REAGENDADA = 'reagendada';
    const ESTADO_CANCELADA = 'cancelada';

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(CitaServicio::class)->orderBy('orden');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(FotoCita::class);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    public function calificacion(): HasMany
    {
        return $this->hasMany(Calificacion::class);
    }

    // Accessors
    public function getFechaFinAttribute(): Carbon
    {
        return $this->fecha_hora->copy()->addMinutes($this->duracion_total);
    }

    public function getServiciosNombresAttribute(): string
    {
        if ($this->servicios->isEmpty()) {
            return $this->servicio->nombre;
        }
        return $this->servicios->map(fn($s) => $s->servicio->nombre)->implode(', ');
    }

    // Scopes
    public function scopeDelDia($query, $fecha = null)
    {
        $fecha = $fecha ?? now();
        return $query->whereDate('fecha_hora', $fecha);
    }

    public function scopeDelEmpleado($query, $empleadoId)
    {
        return $query->where('empleado_id', $empleadoId);
    }

    public function scopeDelCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopeConfirmadas($query)
    {
        return $query->where('estado', self::ESTADO_CONFIRMADA);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    public function scopeActivas($query)
    {
        return $query->whereNotIn('estado', [self::ESTADO_CANCELADA, self::ESTADO_COMPLETADA]);
    }

    public function scopeFuturas($query)
    {
        return $query->where('fecha_hora', '>', now());
    }

    public function scopeEnRango($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha_hora', [$inicio, $fin]);
    }

    // Helpers
    public function puedeCancelarse(): bool
    {
        return $this->estado === self::ESTADO_CONFIRMADA
            && $this->fecha_hora > now();
    }

    public function puedeModificarse(): bool
    {
        return $this->estado === self::ESTADO_CONFIRMADA
            && $this->fecha_hora > now();
    }

    public function estaCompletada(): bool
    {
        return $this->estado === self::ESTADO_COMPLETADA;
    }
}

