<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglaAnticipoServicio extends Model
{
    protected $table = 'reglas_anticipo_servicio';

    protected $fillable = [
        'regla_anticipo_id',
        'servicio_id',
    ];

    // Relaciones
    public function reglaAnticipo(): BelongsTo
    {
        return $this->belongsTo(ReglaAnticipo::class, 'regla_anticipo_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }
}
