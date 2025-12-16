<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglaAnticipoFecha extends Model
{
    protected $table = 'reglas_anticipo_fecha';

    protected $fillable = [
        'regla_anticipo_id',
        'fecha_inicio',
        'fecha_fin',
        'aplica_todos_dias',
        'dias_semana',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'aplica_todos_dias' => 'boolean',
        'dias_semana' => 'array',
    ];

    // Relaciones
    public function reglaAnticipo(): BelongsTo
    {
        return $this->belongsTo(ReglaAnticipo::class, 'regla_anticipo_id');
    }

    // Helpers
    public function aplicaEnFecha($fecha): bool
    {
        $fechaObj = is_string($fecha) ? \Carbon\Carbon::parse($fecha) : $fecha;
        
        if ($fechaObj->lt($this->fecha_inicio) || $fechaObj->gt($this->fecha_fin)) {
            return false;
        }

        if ($this->aplica_todos_dias) {
            return true;
        }

        if (!$this->dias_semana || empty($this->dias_semana)) {
            return false;
        }

        $diaSemana = $fechaObj->dayOfWeekIso; // 1=lunes, 7=domingo
        return in_array($diaSemana, $this->dias_semana);
    }
}
