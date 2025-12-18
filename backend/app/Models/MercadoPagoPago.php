<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MercadoPagoPago extends Model
{
    protected $table = 'mercado_pago_pagos';

    protected $fillable = [
        'payment_id',
        'preference_id',
        'external_reference',
        'monto',
        'estado',
        'payer_email',
        'datos_pago',
        'fecha_aprobacion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'datos_pago' => 'array',
        'fecha_aprobacion' => 'datetime',
    ];

    // Estados posibles
    const ESTADO_PENDING = 'pending';
    const ESTADO_APPROVED = 'approved';
    const ESTADO_REJECTED = 'rejected';
    const ESTADO_CANCELLED = 'cancelled';
    const ESTADO_REFUNDED = 'refunded';

    /**
     * Verificar si el pago está aprobado
     */
    public function estaAprobado(): bool
    {
        return $this->estado === self::ESTADO_APPROVED;
    }

    /**
     * Verificar si el pago está pendiente
     */
    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDING;
    }

    /**
     * Verificar si el pago fue rechazado
     */
    public function estaRechazado(): bool
    {
        return in_array($this->estado, [
            self::ESTADO_REJECTED,
            self::ESTADO_CANCELLED,
            self::ESTADO_REFUNDED,
        ]);
    }
}
