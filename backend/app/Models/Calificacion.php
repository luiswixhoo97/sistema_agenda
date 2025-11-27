<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calificacion extends Model
{
    protected $table = 'calificaciones';

    protected $fillable = [
        'cita_id',
        'cliente_id',
        'empleado_id',
        'puntuacion',
        'comentario',
        'visible',
    ];

    protected $casts = [
        'puntuacion' => 'integer',
        'visible' => 'boolean',
    ];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    // Scopes
    public function scopeVisibles($query)
    {
        return $query->where('visible', true);
    }

    public function scopeDelEmpleado($query, int $empleadoId)
    {
        return $query->where('empleado_id', $empleadoId);
    }

    public function scopeConPuntuacion($query, int $minimo)
    {
        return $query->where('puntuacion', '>=', $minimo);
    }
}

