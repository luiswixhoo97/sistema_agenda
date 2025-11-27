<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteSession extends Model
{
    protected $table = 'cliente_sessions';

    protected $fillable = [
        'cliente_id',
        'token_id',
        'device_info',
        'ip_address',
        'last_activity',
    ];

    protected $casts = [
        'device_info' => 'array',
        'last_activity' => 'datetime',
    ];

    public $timestamps = false;

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function actualizarActividad(): void
    {
        $this->update(['last_activity' => now()]);
    }

    // Scopes
    public function scopeActivas($query, int $minutosInactividad = 60)
    {
        return $query->where('last_activity', '>=', now()->subMinutes($minutosInactividad));
    }

    public function scopeDelCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }
}

