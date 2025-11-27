<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioEmpleado extends Model
{
    protected $table = 'horarios_empleados';

    protected $fillable = [
        'empleado_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'active',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'active' => 'boolean',
    ];

    const LUNES = 1;
    const MARTES = 2;
    const MIERCOLES = 3;
    const JUEVES = 4;
    const VIERNES = 5;
    const SABADO = 6;
    const DOMINGO = 7;

    public static array $diasSemana = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function getNombreDiaAttribute(): string
    {
        return self::$diasSemana[$this->dia_semana] ?? '';
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeDelDia($query, int $diaSemana)
    {
        return $query->where('dia_semana', $diaSemana);
    }
}

