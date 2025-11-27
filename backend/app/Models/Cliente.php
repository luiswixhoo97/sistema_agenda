<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Cliente extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $fillable = [
        'nombre',
        'telefono',
        'email',
        'fecha_nacimiento',
        'notas',
        'preferencia_contacto',
        'notificaciones_push',
        'notificaciones_email',
        'notificaciones_whatsapp',
        'active',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'notificaciones_push' => 'boolean',
        'notificaciones_email' => 'boolean',
        'notificaciones_whatsapp' => 'boolean',
        'active' => 'boolean',
    ];

    // Relaciones
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    public function dispositivos(): HasMany
    {
        return $this->hasMany(Dispositivo::class);
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(ClienteSession::class);
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
              ->orWhere('telefono', 'like', "%{$termino}%")
              ->orWhere('email', 'like', "%{$termino}%");
        });
    }

    // Helpers
    public function quiereNotificacion(string $medio): bool
    {
        return match ($medio) {
            'push' => $this->notificaciones_push,
            'email' => $this->notificaciones_email,
            'whatsapp' => $this->notificaciones_whatsapp,
            default => false,
        };
    }
}

