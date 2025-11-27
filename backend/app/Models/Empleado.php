<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empleado extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'foto',
        'bio',
        'especialidades',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // Relaciones
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'empleado_servicio')
            ->withPivot('precio_especial')
            ->withTimestamps();
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioEmpleado::class);
    }

    public function bloqueos(): HasMany
    {
        return $this->hasMany(BloqueoTiempo::class);
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class);
    }

    // Accessors
    public function getNombreAttribute(): string
    {
        return $this->user->nombre;
    }

    public function getPromedioCalificacionAttribute(): float
    {
        return $this->calificaciones()->avg('puntuacion') ?? 0;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeConServicio($query, $servicioId)
    {
        return $query->whereHas('servicios', function ($q) use ($servicioId) {
            $q->where('servicios.id', $servicioId);
        });
    }
}

