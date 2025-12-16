<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReglaAnticipo extends Model
{
    protected $table = 'reglas_anticipo';

    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo_regla',
        'tipo_calculo',
        'valor_calculo',
        'prioridad',
        'activo',
    ];

    protected $casts = [
        'valor_calculo' => 'decimal:2',
        'prioridad' => 'integer',
        'activo' => 'boolean',
    ];

    const TIPO_FECHA = 'fecha';
    const TIPO_MONTO = 'monto';
    const TIPO_SERVICIO = 'servicio';

    const CALCULO_PORCENTAJE = 'porcentaje';
    const CALCULO_MONTO_FIJO = 'monto_fijo';

    // Relaciones
    public function reglaFecha(): HasOne
    {
        return $this->hasOne(ReglaAnticipoFecha::class, 'regla_anticipo_id');
    }

    public function reglaMonto(): HasOne
    {
        return $this->hasOne(ReglaAnticipoMonto::class, 'regla_anticipo_id');
    }

    public function reglasServicio(): HasMany
    {
        return $this->hasMany(ReglaAnticipoServicio::class, 'regla_anticipo_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_regla', $tipo);
    }

    public function scopeOrdenadasPorPrioridad($query)
    {
        return $query->orderByDesc('prioridad');
    }
}
