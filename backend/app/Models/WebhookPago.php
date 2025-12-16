<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookPago extends Model
{
    protected $table = 'webhooks_pagos';

    public $timestamps = false;

    protected $fillable = [
        'venta_id',
        'proveedor',
        'evento_tipo',
        'payload',
        'procesado',
        'respuesta',
        'error_message',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'respuesta' => 'array',
        'procesado' => 'boolean',
        'created_at' => 'datetime',
    ];

    const PROVEEDOR_MERCADOPAGO = 'mercadopago';
    const PROVEEDOR_STRIPE = 'stripe';

    // Relaciones
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    // Scopes
    public function scopeNoProcesados($query)
    {
        return $query->where('procesado', false);
    }

    public function scopeDelProveedor($query, $proveedor)
    {
        return $query->where('proveedor', $proveedor);
    }
}
