<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FotoService;
use App\Models\Cita;
use App\Models\FotoCita;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FotoController extends Controller
{
    protected FotoService $fotoService;

    public function __construct(FotoService $fotoService)
    {
        $this->fotoService = $fotoService;
    }

    /**
     * Subir fotos a una cita
     */
    public function subir(Request $request, int $citaId): JsonResponse
    {
        $request->validate([
            'fotos' => 'required|array|max:10',
            'fotos.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240', // 10MB max
            'tipo' => 'required|in:antes,durante,despues',
        ]);

        $cita = Cita::findOrFail($citaId);

        // Verificar permisos
        $user = $request->user();
        if ($user->role->nombre === 'cliente') {
            $cliente = $user->cliente ?? null;
            if (!$cliente || $cita->cliente_id !== $cliente->id) {
                return response()->json(['message' => 'No autorizado'], 403);
            }
        }

        try {
            $fotos = $this->fotoService->subirMultiplesFotos(
                $request->file('fotos'),
                $citaId,
                $request->tipo
            );

            return response()->json([
                'message' => 'Fotos subidas exitosamente',
                'fotos' => collect($fotos)->map(fn($f) => [
                    'id' => $f->id,
                    'url' => $this->fotoService->obtenerUrl($f->url, 'medium'),
                    'tipo' => $f->tipo,
                ]),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al subir fotos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener fotos de una cita
     */
    public function index(Request $request, int $citaId): JsonResponse
    {
        $cita = Cita::findOrFail($citaId);

        // Verificar permisos
        $user = $request->user();
        if ($user->role->nombre === 'cliente') {
            $cliente = $user->cliente ?? null;
            if (!$cliente || $cita->cliente_id !== $cliente->id) {
                return response()->json(['message' => 'No autorizado'], 403);
            }
        }

        $tipo = $request->query('tipo');
        $fotos = $this->fotoService->obtenerFotosCita($citaId, $tipo);

        return response()->json([
            'fotos' => $fotos,
        ]);
    }

    /**
     * Eliminar una foto
     */
    public function destroy(Request $request, int $fotoId): JsonResponse
    {
        $foto = FotoCita::with('cita')->findOrFail($fotoId);

        // Verificar permisos (solo admin/empleado puede eliminar)
        $user = $request->user();
        if (!in_array($user->role->nombre, ['admin', 'empleado'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($this->fotoService->eliminarFoto($foto)) {
            return response()->json(['message' => 'Foto eliminada']);
        }

        return response()->json(['message' => 'Error al eliminar foto'], 500);
    }

    /**
     * Obtener galería de un cliente
     */
    public function galeriaCliente(Request $request, int $clienteId): JsonResponse
    {
        // Verificar permisos
        $user = $request->user();
        if ($user->role->nombre === 'cliente') {
            $cliente = $user->cliente ?? null;
            if (!$cliente || $cliente->id !== $clienteId) {
                return response()->json(['message' => 'No autorizado'], 403);
            }
        }

        $galeria = $this->fotoService->obtenerGaleriaCliente($clienteId);

        return response()->json([
            'galeria' => $galeria,
        ]);
    }
}

