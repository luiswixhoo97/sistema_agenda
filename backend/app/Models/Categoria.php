<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}

