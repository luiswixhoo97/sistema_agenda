<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'telefono',
        'tipo',
        'codigo',
        'intentos',
        'verificado',
        'expira_at',
        'created_at',
    ];

    protected $casts = [
        'intentos' => 'integer',
        'verificado' => 'boolean',
        'expira_at' => 'datetime',
    ];

    public $timestamps = false;

    const MAX_INTENTOS = 3;
    const MINUTOS_EXPIRACION = 5;

    /**
     * Crear nuevo OTP para un teléfono
     */
    public static function generar(string $telefono): self
    {
        // Invalidar OTPs anteriores
        self::where('telefono', $telefono)
            ->where('verificado', false)
            ->delete();

        return self::create([
            'telefono' => $telefono,
            'codigo' => self::generarCodigo(),
            'intentos' => 0,
            'verificado' => false,
            'expira_at' => now()->addMinutes(self::MINUTOS_EXPIRACION),
            'created_at' => now(),
        ]);
    }

    /**
     * Generar código de 6 dígitos
     */
    private static function generarCodigo(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verificar OTP
     */
    public static function verificar(string $telefono, string $codigo): array
    {
        $otp = self::where('telefono', $telefono)
            ->where('verificado', false)
            ->where('expira_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otp) {
            return [
                'valido' => false,
                'error' => 'Código expirado o no encontrado',
                'intentos_restantes' => 0,
            ];
        }

        if ($otp->intentos >= self::MAX_INTENTOS) {
            return [
                'valido' => false,
                'error' => 'Máximo de intentos alcanzado',
                'intentos_restantes' => 0,
            ];
        }

        if ($otp->codigo !== $codigo) {
            $otp->increment('intentos');
            return [
                'valido' => false,
                'error' => 'Código incorrecto',
                'intentos_restantes' => self::MAX_INTENTOS - $otp->intentos,
            ];
        }

        // Código válido
        $otp->update(['verificado' => true]);

        return [
            'valido' => true,
            'error' => null,
            'intentos_restantes' => null,
        ];
    }

    /**
     * Limpiar OTPs expirados
     */
    public static function limpiarExpirados(): int
    {
        return self::where('expira_at', '<', now())->delete();
    }

    // Scopes
    public function scopeNoVerificados($query)
    {
        return $query->where('verificado', false);
    }

    public function scopeValidos($query)
    {
        return $query->where('expira_at', '>', now())
                     ->where('verificado', false);
    }

    // Helpers
    public function estaExpirado(): bool
    {
        return $this->expira_at < now();
    }

    public function tieneIntentosDisponibles(): bool
    {
        return $this->intentos < self::MAX_INTENTOS;
    }
}

