<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $table = 'login_attempts';

    protected $fillable = [
        'identificador',
        'tipo',
        'ip_address',
        'exitoso',
        'user_agent',
    ];

    protected $casts = [
        'exitoso' => 'boolean',
    ];

    public $timestamps = false;

    const TIPO_EMPLEADO = 'empleado';
    const TIPO_CLIENTE = 'cliente';
    const TIPO_ADMIN = 'admin';

    /**
     * Registrar intento de login
     */
    public static function registrar(
        string $identificador,
        string $tipo,
        bool $exitoso
    ): self {
        return self::create([
            'identificador' => $identificador,
            'tipo' => $tipo,
            'ip_address' => request()->ip(),
            'exitoso' => $exitoso,
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Contar intentos fallidos recientes
     */
    public static function intentosFallidos(string $identificador, int $minutos = 15): int
    {
        return self::where('identificador', $identificador)
            ->where('exitoso', false)
            ->where('created_at', '>=', now()->subMinutes($minutos))
            ->count();
    }

    /**
     * Verificar si está bloqueado
     */
    public static function estaBloqueado(string $identificador, int $maxIntentos = 5, int $minutos = 15): bool
    {
        return self::intentosFallidos($identificador, $minutos) >= $maxIntentos;
    }

    // Scopes
    public function scopeExitosos($query)
    {
        return $query->where('exitoso', true);
    }

    public function scopeFallidos($query)
    {
        return $query->where('exitoso', false);
    }

    public function scopeRecientes($query, int $minutos = 15)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutos));
    }

    public function scopeDeIdentificador($query, string $identificador)
    {
        return $query->where('identificador', $identificador);
    }
}

