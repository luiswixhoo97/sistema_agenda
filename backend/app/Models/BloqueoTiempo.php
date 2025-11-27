<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloqueoTiempo extends Model
{
    protected $table = 'bloqueos_tiempo';

    protected $fillable = [
        'empleado_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'tipo',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    const TIPO_ALMUERZO = 'almuerzo';
    const TIPO_LIMPIEZA = 'limpieza';
    const TIPO_LIBRE = 'libre';
    const TIPO_REUNION = 'reunion';
    const TIPO_OTRO = 'otro';

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    // Si empleado_id es null, aplica a todos
    public function esGlobal(): bool
    {
        return is_null($this->empleado_id);
    }

    public function scopeDelDia($query, $fecha)
    {
        return $query->where('fecha', $fecha);
    }

    public function scopeDelEmpleado($query, $empleadoId)
    {
        return $query->where(function ($q) use ($empleadoId) {
            $q->where('empleado_id', $empleadoId)
              ->orWhereNull('empleado_id');
        });
    }

    public function scopeGlobales($query)
    {
        return $query->whereNull('empleado_id');
    }
}

