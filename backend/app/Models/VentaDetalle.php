<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaDetalle extends Model
{
    protected $table = 'venta_detalle';

    protected $fillable = [
        'venta_id',
        'tipo',
        'producto_id',
        'servicio_id',
        'cita_id',
        'promocion_id',
        'cantidad',
        'precio_unitario',
        'descuento',
        'impuesto',
        'subtotal_linea',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'subtotal_linea' => 'decimal:2',
    ];

    const TIPO_PRODUCTO = 'producto';
    const TIPO_SERVICIO = 'servicio';

    // Relaciones
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'cita_id');
    }

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class, 'promocion_id');
    }
}
