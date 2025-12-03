<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promocion extends Model
{
    use SoftDeletes;

    protected $table = 'promociones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'imagen',
        'descuento_porcentaje',
        'descuento_fijo',
        'fecha_inicio',
        'fecha_fin',
        'servicios_aplicables',
        'usos_maximos',
        'usos_actuales',
        'active',
    ];

    protected $casts = [
        'descuento_porcentaje' => 'integer',
        'descuento_fijo' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'servicios_aplicables' => 'array',
        'usos_maximos' => 'integer',
        'usos_actuales' => 'integer',
        'active' => 'boolean',
    ];

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeVigentes($query)
    {
        $hoy = now()->toDateString();
        return $query->where('fecha_inicio', '<=', $hoy)
                     ->where('fecha_fin', '>=', $hoy);
    }

    public function scopeDisponibles($query)
    {
        return $query->active()
                     ->vigentes()
                     ->where(function ($q) {
                         $q->whereNull('usos_maximos')
                           ->orWhereColumn('usos_actuales', '<', 'usos_maximos');
                     });
    }

    // Helpers
    public function estaVigente(): bool
    {
        $hoy = now()->toDateString();
        return $this->fecha_inicio <= $hoy && $this->fecha_fin >= $hoy;
    }

    public function tieneUsosDisponibles(): bool
    {
        if (is_null($this->usos_maximos)) {
            return true;
        }
        return $this->usos_actuales < $this->usos_maximos;
    }

    public function aplicaAServicio(int $servicioId): bool
    {
        if (empty($this->servicios_aplicables)) {
            return true; // Aplica a todos
        }
        return in_array($servicioId, $this->servicios_aplicables);
    }

    public function calcularDescuento(float $precio): float
    {
        if ($this->descuento_porcentaje) {
            return $precio * ($this->descuento_porcentaje / 100);
        }
        if ($this->descuento_fijo) {
            return min($this->descuento_fijo, $precio);
        }
        return 0;
    }

    public function incrementarUso(): void
    {
        $this->increment('usos_actuales');
    }
}

