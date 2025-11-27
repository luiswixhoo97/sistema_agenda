<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditoria';

    protected $fillable = [
        'usuario_id',
        'accion',
        'tabla',
        'registro_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    public $timestamps = false;

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Registrar acción de auditoría
     */
    public static function registrar(
        string $accion,
        string $tabla,
        int $registroId,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?int $usuarioId = null
    ): self {
        return self::create([
            'usuario_id' => $usuarioId ?? auth()->id(),
            'accion' => $accion,
            'tabla' => $tabla,
            'registro_id' => $registroId,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    // Scopes
    public function scopeDeTabla($query, string $tabla)
    {
        return $query->where('tabla', $tabla);
    }

    public function scopeDeRegistro($query, string $tabla, int $registroId)
    {
        return $query->where('tabla', $tabla)->where('registro_id', $registroId);
    }

    public function scopeDeUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeRecientes($query, int $dias = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }
}

