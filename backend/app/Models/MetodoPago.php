<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $fillable = [
        'nombre',
        'codigo',
        'es_efectivo',
        'activo',
        'orden',
    ];

    protected $casts = [
        'es_efectivo' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    // Relaciones
    public function ventaPagos(): HasMany
    {
        return $this->hasMany(VentaPago::class, 'metodo_pago_id');
    }

    // Scopes
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }
}
