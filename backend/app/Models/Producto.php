<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'foto',
        'categoria_id',
        'inventario_minimo',
        'inventario_actual',
        'costo',
        'precio',
        'active',
    ];

    protected $casts = [
        'inventario_minimo' => 'integer',
        'inventario_actual' => 'integer',
        'costo' => 'decimal:2',
        'precio' => 'decimal:2',
        'active' => 'boolean',
    ];

    // Relaciones
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }

    public function ventaDetalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'producto_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeDeCategoria($query, $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('codigo', 'like', "%{$termino}%")
              ->orWhere('nombre', 'like', "%{$termino}%");
        });
    }

    // Helpers
    public function tieneStock(int $cantidad): bool
    {
        return $this->inventario_actual >= $cantidad;
    }

    public function stockBajo(): bool
    {
        return $this->inventario_actual <= $this->inventario_minimo;
    }
}
