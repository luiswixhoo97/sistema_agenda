<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantillaNotificacion extends Model
{
    protected $table = 'plantillas_notificacion';

    protected $fillable = [
        'tipo',
        'medio',
        'asunto',
        'contenido',
        'variables',
        'activo',
    ];

    protected $casts = [
        'variables' => 'array',
        'activo' => 'boolean',
    ];

    /**
     * Obtener plantilla por tipo y medio
     */
    public static function obtener(string $tipo, string $medio): ?self
    {
        return self::where('tipo', $tipo)
            ->where('medio', $medio)
            ->where('activo', true)
            ->first();
    }

    /**
     * Renderizar contenido con variables
     */
    public function renderizar(array $datos): string
    {
        $contenido = $this->contenido;
        
        foreach ($datos as $variable => $valor) {
            $contenido = str_replace("{{{$variable}}}", $valor, $contenido);
        }
        
        return $contenido;
    }

    /**
     * Renderizar asunto con variables (para email)
     */
    public function renderizarAsunto(array $datos): ?string
    {
        if (!$this->asunto) {
            return null;
        }

        $asunto = $this->asunto;
        
        foreach ($datos as $variable => $valor) {
            $asunto = str_replace("{{{$variable}}}", $valor, $asunto);
        }
        
        return $asunto;
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDelTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeDelMedio($query, string $medio)
    {
        return $query->where('medio', $medio);
    }
}

