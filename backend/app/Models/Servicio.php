<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Servicio extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'precio',
        'duracion',
        'active',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'duracion' => 'integer',
        'active' => 'boolean',
    ];

    // Relaciones
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function empleados(): BelongsToMany
    {
        return $this->belongsToMany(Empleado::class, 'empleado_servicio')
            ->withPivot('precio_especial')
            ->withTimestamps();
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function citasServicios(): HasMany
    {
        return $this->hasMany(CitaServicio::class);
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
}

