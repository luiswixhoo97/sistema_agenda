<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispositivo extends Model
{
    protected $fillable = [
        'cliente_id',
        'user_id',
        'token_push',
        'plataforma',
        'modelo',
        'activo',
        'last_used_at',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    const PLATAFORMA_ANDROID = 'android';
    const PLATAFORMA_IOS = 'ios';
    const PLATAFORMA_WEB = 'web';

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDelCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopeDelUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePlataforma($query, string $plataforma)
    {
        return $query->where('plataforma', $plataforma);
    }

    // Helpers
    public function marcarUsado(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function desactivar(): void
    {
        $this->update(['activo' => false]);
    }
}

