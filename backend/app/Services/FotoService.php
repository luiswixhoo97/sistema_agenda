<?php

namespace App\Services;

use App\Models\FotoCita;
use App\Models\Cita;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class FotoService
{
    protected string $disk;
    protected string $basePath;
    protected array $tamanos;

    public function __construct()
    {
        $this->disk = config('filesystems.default', 'local');
        $this->basePath = 'fotos_citas';
        $this->tamanos = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ];
    }

    /**
     * Subir foto de cita
     */
    public function subirFoto(UploadedFile $archivo, int $citaId, string $tipo = 'durante'): FotoCita
    {
        // Validar tipo
        if (!in_array($tipo, ['antes', 'durante', 'despues'])) {
            throw new \InvalidArgumentException('Tipo de foto inválido');
        }

        // Generar nombre único
        $nombreOriginal = $archivo->getClientOriginalName();
        $extension = $archivo->getClientOriginalExtension();
        $nombreUnico = Str::uuid() . '.' . $extension;
        
        // Crear directorio por cita
        $directorio = "{$this->basePath}/{$citaId}";
        
        // Guardar imagen original (comprimida)
        $rutaOriginal = $this->guardarImagenOptimizada($archivo, $directorio, $nombreUnico);
        
        // Generar thumbnails
        $rutas = $this->generarThumbnails($archivo, $directorio, $nombreUnico);
        
        // Registrar en base de datos
        $foto = FotoCita::create([
            'cita_id' => $citaId,
            'url' => $rutaOriginal,
            'tipo' => $tipo,
            'metadata' => json_encode([
                'nombre_original' => $nombreOriginal,
                'tamano' => $archivo->getSize(),
                'mime' => $archivo->getMimeType(),
                'thumbnails' => $rutas,
            ]),
        ]);

        return $foto;
    }

    /**
     * Subir múltiples fotos
     */
    public function subirMultiplesFotos(array $archivos, int $citaId, string $tipo = 'durante'): array
    {
        $fotos = [];
        
        foreach ($archivos as $archivo) {
            if ($archivo instanceof UploadedFile && $archivo->isValid()) {
                $fotos[] = $this->subirFoto($archivo, $citaId, $tipo);
            }
        }
        
        return $fotos;
    }

    /**
     * Guardar imagen optimizada
     */
    protected function guardarImagenOptimizada(UploadedFile $archivo, string $directorio, string $nombre): string
    {
        try {
            // Usar Intervention Image si está disponible
            if (class_exists('Intervention\Image\Facades\Image')) {
                $imagen = Image::make($archivo);
                
                // Redimensionar si es muy grande
                if ($imagen->width() > 1920 || $imagen->height() > 1920) {
                    $imagen->resize(1920, 1920, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
                
                // Comprimir
                $contenido = $imagen->encode('jpg', 85)->getEncoded();
                $nombre = pathinfo($nombre, PATHINFO_FILENAME) . '.jpg';
                
                $ruta = "{$directorio}/original_{$nombre}";
                Storage::disk($this->disk)->put($ruta, $contenido);
                
                return $ruta;
            }
        } catch (\Exception $e) {
            Log::warning("Intervention Image no disponible, guardando sin optimizar: " . $e->getMessage());
        }
        
        // Fallback: guardar sin procesar
        $ruta = "{$directorio}/original_{$nombre}";
        Storage::disk($this->disk)->putFileAs($directorio, $archivo, "original_{$nombre}");
        
        return $ruta;
    }

    /**
     * Generar thumbnails
     */
    protected function generarThumbnails(UploadedFile $archivo, string $directorio, string $nombre): array
    {
        $rutas = [];
        
        try {
            if (!class_exists('Intervention\Image\Facades\Image')) {
                return $rutas;
            }

            $imagen = Image::make($archivo);
            $nombreBase = pathinfo($nombre, PATHINFO_FILENAME);

            foreach ($this->tamanos as $tamano => $dimensiones) {
                $imagenRedimensionada = clone $imagen;
                
                $imagenRedimensionada->fit($dimensiones['width'], $dimensiones['height'], function ($constraint) {
                    $constraint->upsize();
                });
                
                $contenido = $imagenRedimensionada->encode('jpg', 80)->getEncoded();
                $rutaThumbnail = "{$directorio}/{$tamano}_{$nombreBase}.jpg";
                
                Storage::disk($this->disk)->put($rutaThumbnail, $contenido);
                $rutas[$tamano] = $rutaThumbnail;
            }
            
        } catch (\Exception $e) {
            Log::error("Error generando thumbnails: " . $e->getMessage());
        }
        
        return $rutas;
    }

    /**
     * Obtener URL pública de foto
     */
    public function obtenerUrl(string $ruta, string $tamano = 'original'): string
    {
        if ($tamano !== 'original') {
            // Intentar obtener thumbnail
            $directorio = dirname($ruta);
            $nombre = basename($ruta);
            $nombreBase = preg_replace('/^original_/', '', $nombre);
            $nombreBase = pathinfo($nombreBase, PATHINFO_FILENAME);
            $rutaThumbnail = "{$directorio}/{$tamano}_{$nombreBase}.jpg";
            
            if (Storage::disk($this->disk)->exists($rutaThumbnail)) {
                $ruta = $rutaThumbnail;
            }
        }
        
        return Storage::disk($this->disk)->url($ruta);
    }

    /**
     * Eliminar foto
     */
    public function eliminarFoto(FotoCita $foto): bool
    {
        try {
            // Eliminar archivo original
            if (Storage::disk($this->disk)->exists($foto->url)) {
                Storage::disk($this->disk)->delete($foto->url);
            }
            
            // Eliminar thumbnails
            $metadata = json_decode($foto->metadata, true);
            if (isset($metadata['thumbnails'])) {
                foreach ($metadata['thumbnails'] as $ruta) {
                    if (Storage::disk($this->disk)->exists($ruta)) {
                        Storage::disk($this->disk)->delete($ruta);
                    }
                }
            }
            
            // Eliminar registro
            $foto->delete();
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error eliminando foto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener fotos de una cita
     */
    public function obtenerFotosCita(int $citaId, ?string $tipo = null): array
    {
        $query = FotoCita::where('cita_id', $citaId);
        
        if ($tipo) {
            $query->where('tipo', $tipo);
        }
        
        $fotos = $query->orderBy('created_at')->get();
        
        return $fotos->map(function ($foto) {
            $metadata = json_decode($foto->metadata, true);
            return [
                'id' => $foto->id,
                'url' => $this->obtenerUrl($foto->url),
                'url_thumbnail' => $this->obtenerUrl($foto->url, 'thumbnail'),
                'url_medium' => $this->obtenerUrl($foto->url, 'medium'),
                'tipo' => $foto->tipo,
                'nombre_original' => $metadata['nombre_original'] ?? '',
                'created_at' => $foto->created_at,
            ];
        })->toArray();
    }

    /**
     * Obtener galería de un cliente (todas sus citas)
     */
    public function obtenerGaleriaCliente(int $clienteId): array
    {
        $citas = Cita::where('cliente_id', $clienteId)
            ->with('fotos')
            ->orderBy('fecha_hora', 'desc')
            ->get();
        
        $galeria = [];
        
        foreach ($citas as $cita) {
            if ($cita->fotos->isNotEmpty()) {
                $galeria[] = [
                    'cita_id' => $cita->id,
                    'fecha' => $cita->fecha_hora->format('Y-m-d'),
                    'fotos' => $this->obtenerFotosCita($cita->id),
                ];
            }
        }
        
        return $galeria;
    }

    /**
     * Limpiar fotos antiguas (job programado)
     */
    public function limpiarFotosAntiguas(int $diasAntiguedad = 365): int
    {
        $fechaLimite = now()->subDays($diasAntiguedad);
        
        $fotos = FotoCita::whereHas('cita', function ($query) use ($fechaLimite) {
            $query->where('fecha_hora', '<', $fechaLimite);
        })->get();
        
        $eliminadas = 0;
        
        foreach ($fotos as $foto) {
            if ($this->eliminarFoto($foto)) {
                $eliminadas++;
            }
        }
        
        Log::info("Limpieza de fotos antiguas: {$eliminadas} eliminadas");
        
        return $eliminadas;
    }
}

