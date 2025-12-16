<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaPago extends Model
{
    protected $table = 'venta_pagos';

    protected $fillable = [
        'venta_id',
        'metodo_pago_id',
        'monto',
        'monto_recibido',
        'cambio',
        'transaccion_id',
        'proveedor_pago',
        'estado_pago',
        'metadata_pago',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'monto_recibido' => 'decimal:2',
        'cambio' => 'decimal:2',
        'metadata_pago' => 'array',
    ];

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_APROBADO = 'aprobado';
    const ESTADO_RECHAZADO = 'rechazado';
    const ESTADO_REEMBOLSADO = 'reembolsado';

    const PROVEEDOR_MERCADOPAGO = 'mercadopago';
    const PROVEEDOR_STRIPE = 'stripe';

    // Relaciones
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeAprobados($query)
    {
        return $query->where('estado_pago', self::ESTADO_APROBADO);
    }

    public function scopePendientes($query)
    {
        return $query->where('estado_pago', self::ESTADO_PENDIENTE);
    }

    // Helpers
    public function estaAprobado(): bool
    {
        return $this->estado_pago === self::ESTADO_APROBADO;
    }
}
