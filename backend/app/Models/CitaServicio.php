<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitaServicio extends Model
{
    protected $table = 'citas_servicios';

    protected $fillable = [
        'cita_id',
        'servicio_id',
        'precio_aplicado',
        'orden',
    ];

    protected $casts = [
        'precio_aplicado' => 'decimal:2',
        'orden' => 'integer',
    ];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}

