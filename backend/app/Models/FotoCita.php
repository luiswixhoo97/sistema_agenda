<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoCita extends Model
{
    protected $table = 'fotos_citas';

    protected $fillable = [
        'cita_id',
        'tipo',
        'ruta_archivo',
        'descripcion',
    ];

    const TIPO_ANTES = 'antes';
    const TIPO_DESPUES = 'despues';
    const TIPO_PROCESO = 'proceso';

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->ruta_archivo);
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }
}

