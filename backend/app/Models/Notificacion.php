<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'cita_id',
        'cliente_id',
        'tipo',
        'medio',
        'estado',
        'enviado_at',
        'leido_at',
        'intentos',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'enviado_at' => 'datetime',
        'leido_at' => 'datetime',
        'intentos' => 'integer',
        'metadata' => 'array',
    ];

    const TIPO_NUEVA_CITA = 'nueva_cita';
    const TIPO_CONFIRMACION = 'confirmacion';
    const TIPO_RECORDATORIO = 'recordatorio';
    const TIPO_RECORDATORIO_DIA = 'recordatorio_dia';
    const TIPO_CANCELACION = 'cancelacion';
    const TIPO_MODIFICACION = 'modificacion';
    const TIPO_RECORDATORIO_EMPLEADO = 'recordatorio_empleado';
    const TIPO_PROMOCION = 'promocion';

    const MEDIO_PUSH = 'push';
    const MEDIO_EMAIL = 'email';
    const MEDIO_WHATSAPP = 'whatsapp';

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_ENVIADA = 'enviada';
    const ESTADO_FALLIDA = 'fallida';
    const ESTADO_LEIDA = 'leida';

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    public function scopeEnviadas($query)
    {
        return $query->where('estado', self::ESTADO_ENVIADA);
    }

    public function scopeFallidas($query)
    {
        return $query->where('estado', self::ESTADO_FALLIDA);
    }

    public function scopeDelMedio($query, string $medio)
    {
        return $query->where('medio', $medio);
    }

    public function scopeDelTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    // Helpers
    public function marcarEnviada(): void
    {
        $this->update([
            'estado' => self::ESTADO_ENVIADA,
            'enviado_at' => now(),
        ]);
    }

    public function marcarFallida(string $error): void
    {
        $this->update([
            'estado' => self::ESTADO_FALLIDA,
            'error_message' => $error,
            'intentos' => $this->intentos + 1,
        ]);
    }

    public function marcarLeida(): void
    {
        $this->update([
            'estado' => self::ESTADO_LEIDA,
            'leido_at' => now(),
        ]);
    }

    public function puedeReintentar(int $maxIntentos = 5): bool
    {
        return $this->estado === self::ESTADO_FALLIDA
            && $this->intentos < $maxIntentos;
    }
}

