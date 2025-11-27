<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiaFestivo extends Model
{
    protected $table = 'dias_festivos';

    protected $fillable = [
        'fecha',
        'nombre',
        'aplica_a_todos',
    ];

    protected $casts = [
        'fecha' => 'date',
        'aplica_a_todos' => 'boolean',
    ];

    /**
     * Verificar si una fecha es festivo
     */
    public static function esFestivo($fecha): bool
    {
        return self::where('fecha', $fecha)
            ->where('aplica_a_todos', true)
            ->exists();
    }

    /**
     * Obtener festivos del año
     */
    public static function delAnio(int $anio): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereYear('fecha', $anio)
            ->orderBy('fecha')
            ->get();
    }

    // Scopes
    public function scopeGlobales($query)
    {
        return $query->where('aplica_a_todos', true);
    }

    public function scopeDelMes($query, int $mes, int $anio)
    {
        return $query->whereMonth('fecha', $mes)
                     ->whereYear('fecha', $anio);
    }
}

