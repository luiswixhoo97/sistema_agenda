<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoriaProducto extends Model
{
    use SoftDeletes;

    protected $table = 'categorias_productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // Relaciones
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
