<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use SoftDeletes;

    protected $table = 'ventas';

    protected $fillable = [
        'cliente_id',
        'fecha_venta',
        'subtotal',
        'descuento_general',
        'impuesto_total',
        'total',
        'total_pagado',
        'saldo_pendiente',
        'estado',
        'requiere_anticipo',
        'monto_anticipo_requerido',
        'monto_anticipo_pagado',
        'notas',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento_general' => 'decimal:2',
        'impuesto_total' => 'decimal:2',
        'total' => 'decimal:2',
        'total_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'requiere_anticipo' => 'boolean',
        'monto_anticipo_requerido' => 'decimal:2',
        'monto_anticipo_pagado' => 'decimal:2',
    ];

    const ESTADO_PENDIENTE_PAGO = 'pendiente_pago';
    const ESTADO_PARCIAL = 'parcial';
    const ESTADO_COMPLETADA = 'completada';
    const ESTADO_CANCELADA = 'cancelada';

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'venta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(VentaPago::class, 'venta_id');
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(WebhookPago::class, 'venta_id');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', self::ESTADO_PENDIENTE_PAGO);
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    public function scopeDelCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopeEnRango($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha_venta', [$inicio, $fin]);
    }

    // Helpers
    public function estaCompletada(): bool
    {
        return $this->estado === self::ESTADO_COMPLETADA;
    }

    public function estaCancelada(): bool
    {
        return $this->estado === self::ESTADO_CANCELADA;
    }

    public function puedeModificarse(): bool
    {
        // Permitir modificaciones si está pendiente o parcial (tiene anticipos)
        // No permitir si está completada o cancelada
        return in_array($this->estado, [self::ESTADO_PENDIENTE_PAGO, self::ESTADO_PARCIAL]);
    }

    public function actualizarSaldo(): void
    {
        $this->total_pagado = $this->pagos()->where('estado_pago', 'aprobado')->sum('monto');
        $this->saldo_pendiente = $this->total - $this->total_pagado;
        
        if ($this->saldo_pendiente <= 0) {
            $this->estado = self::ESTADO_COMPLETADA;
        } elseif ($this->total_pagado > 0) {
            $this->estado = self::ESTADO_PARCIAL;
        } else {
            $this->estado = self::ESTADO_PENDIENTE_PAGO;
        }
        
        $this->save();
    }
}
