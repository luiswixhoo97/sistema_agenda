<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    public $timestamps = false;

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'motivo',
        'referencia_id',
        'referencia_tipo',
        'user_id',
        'notas',
        'created_at',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'created_at' => 'datetime',
    ];

    const TIPO_ENTRADA_MANUAL = 'entrada_manual';
    const TIPO_SALIDA_MANUAL = 'salida_manual';
    const TIPO_VENTA = 'venta';

    // Relaciones
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeDelProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeEnRango($query, $inicio, $fin)
    {
        return $query->whereBetween('created_at', [$inicio, $fin]);
    }
}
