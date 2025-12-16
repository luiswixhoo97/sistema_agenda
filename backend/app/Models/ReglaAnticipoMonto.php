<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglaAnticipoMonto extends Model
{
    protected $table = 'reglas_anticipo_monto';

    protected $fillable = [
        'regla_anticipo_id',
        'monto_minimo',
        'monto_maximo',
    ];

    protected $casts = [
        'monto_minimo' => 'decimal:2',
        'monto_maximo' => 'decimal:2',
    ];

    // Relaciones
    public function reglaAnticipo(): BelongsTo
    {
        return $this->belongsTo(ReglaAnticipo::class, 'regla_anticipo_id');
    }

    // Helpers
    public function aplicaAMonto($monto): bool
    {
        if ($monto < $this->monto_minimo) {
            return false;
        }

        if ($this->monto_maximo !== null && $monto > $this->monto_maximo) {
            return false;
        }

        return true;
    }
}
