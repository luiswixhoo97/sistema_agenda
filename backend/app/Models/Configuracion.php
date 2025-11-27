<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Configuracion extends Model
{
    protected $table = 'configuracion';

    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'descripcion',
    ];

    // Cache key prefix
    private const CACHE_PREFIX = 'config_';
    private const CACHE_TTL = 3600; // 1 hora

    /**
     * Obtener valor de configuración
     */
    public static function get(string $clave, $default = null)
    {
        return Cache::remember(self::CACHE_PREFIX . $clave, self::CACHE_TTL, function () use ($clave, $default) {
            $config = self::where('clave', $clave)->first();
            
            if (!$config) {
                return $default;
            }

            return self::castValue($config->valor, $config->tipo);
        });
    }

    /**
     * Establecer valor de configuración
     */
    public static function set(string $clave, $valor): void
    {
        $config = self::where('clave', $clave)->first();
        
        if ($config) {
            $config->update(['valor' => (string) $valor]);
        } else {
            self::create([
                'clave' => $clave,
                'valor' => (string) $valor,
                'tipo' => self::detectType($valor),
            ]);
        }

        Cache::forget(self::CACHE_PREFIX . $clave);
    }

    /**
     * Obtener todas las configuraciones
     */
    public static function getAll(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'all', self::CACHE_TTL, function () {
            $configs = [];
            foreach (self::all() as $config) {
                $configs[$config->clave] = self::castValue($config->valor, $config->tipo);
            }
            return $configs;
        });
    }

    /**
     * Limpiar caché de configuraciones
     */
    public static function clearCache(): void
    {
        $configs = self::pluck('clave');
        foreach ($configs as $clave) {
            Cache::forget(self::CACHE_PREFIX . $clave);
        }
        Cache::forget(self::CACHE_PREFIX . 'all');
    }

    /**
     * Cast valor según tipo
     */
    private static function castValue(string $valor, string $tipo)
    {
        return match ($tipo) {
            'int' => (int) $valor,
            'float' => (float) $valor,
            'boolean' => filter_var($valor, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($valor, true),
            default => $valor,
        };
    }

    /**
     * Detectar tipo de valor
     */
    private static function detectType($valor): string
    {
        if (is_int($valor)) return 'int';
        if (is_float($valor)) return 'float';
        if (is_bool($valor)) return 'boolean';
        if (is_array($valor)) return 'json';
        return 'string';
    }

    // Métodos helper para configuraciones específicas
    public static function horarioApertura(): string
    {
        return self::get('horario_apertura', '09:00');
    }

    public static function horarioCierre(): string
    {
        return self::get('horario_cierre', '20:00');
    }

    public static function duracionSlot(): int
    {
        return self::get('duracion_slot_minutos', 30);
    }

    public static function anticipacionMinima(): int
    {
        return self::get('anticipacion_minima_horas', 2);
    }

    public static function anticipacionMaxima(): int
    {
        return self::get('dias_anticipacion_maxima', 60);
    }

    public static function recordatorioHorasAntes(): int
    {
        return self::get('recordatorio_horas_antes', 24);
    }

    public static function recordatorioDiaHorasAntes(): int
    {
        return self::get('recordatorio_dia_horas_antes', 2);
    }
}

