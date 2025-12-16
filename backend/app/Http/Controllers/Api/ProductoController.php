<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\Auditoria;
use App\Services\QrService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProductoController extends Controller
{
    protected QrService $qrService;

    public function __construct(QrService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Listado de productos
     * 
     * GET /api/admin/productos
     */
    public function index(Request $request): JsonResponse
    {
        $query = Producto::with('categoria');

        // Filtros
        if ($request->has('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }
        if ($request->has('buscar')) {
            $query->buscar($request->buscar);
        }

        $productos = $query->orderBy('nombre')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $productos->map(fn($p) => $this->formatearProducto($p)),
            'pagination' => [
                'current_page' => $productos->currentPage(),
                'last_page' => $productos->lastPage(),
                'total' => $productos->total(),
            ],
        ]);
    }

    /**
     * Ver producto
     * 
     * GET /api/admin/productos/{id}
     */
    public function show(int $id): JsonResponse
    {
        $producto = Producto::with('categoria')->find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatearProducto($producto),
        ]);
    }

    /**
     * Crear producto
     * 
     * POST /api/admin/productos
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'codigo' => 'nullable|string|max:100|unique:productos,codigo',
            'nombre' => 'required|string|max:255',
            'foto' => 'nullable|string|max:255',
            'categoria_id' => 'nullable|exists:categorias_productos,id',
            'inventario_minimo' => 'nullable|integer|min:0',
            'inventario_actual' => 'nullable|integer|min:0',
            'costo' => 'nullable|numeric|min:0',
            'precio' => 'required|numeric|min:0',
        ]);

        // Generar código automático si no se proporciona
        $codigo = $request->codigo ?? $this->generarCodigo();

        $producto = Producto::create([
            'codigo' => $codigo,
            'nombre' => $request->nombre,
            'foto' => $request->foto,
            'categoria_id' => $request->categoria_id,
            'inventario_minimo' => $request->inventario_minimo ?? 0,
            'inventario_actual' => $request->inventario_actual ?? 0,
            'costo' => $request->costo ?? 0,
            'precio' => $request->precio,
            'active' => true,
        ]);

        Auditoria::registrar('crear', 'productos', $producto->id, null, $producto->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Producto creado correctamente',
            'data' => $this->formatearProducto($producto->load('categoria')),
        ], 201);
    }

    /**
     * Actualizar producto
     * 
     * PUT /api/admin/productos/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        $request->validate([
            'codigo' => 'sometimes|string|max:100|unique:productos,codigo,' . $id,
            'nombre' => 'sometimes|string|max:255',
            'foto' => 'nullable|string|max:255',
            'categoria_id' => 'nullable|exists:categorias_productos,id',
            'inventario_minimo' => 'nullable|integer|min:0',
            'costo' => 'nullable|numeric|min:0',
            'precio' => 'sometimes|numeric|min:0',
            'active' => 'sometimes|boolean',
        ]);

        $datosAnteriores = $producto->toArray();
        
        $producto->update($request->only([
            'codigo', 'nombre', 'foto', 'categoria_id', 
            'inventario_minimo', 'costo', 'precio', 'active'
        ]));

        Auditoria::registrar('actualizar', 'productos', $producto->id, $datosAnteriores, $producto->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente',
            'data' => $this->formatearProducto($producto->load('categoria')),
        ]);
    }

    /**
     * Eliminar producto (soft delete)
     * 
     * DELETE /api/admin/productos/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        $datosAnteriores = $producto->toArray();
        $producto->delete();

        Auditoria::registrar('eliminar', 'productos', $id, $datosAnteriores, null);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente',
        ]);
    }

    /**
     * Buscar productos para punto de venta
     * 
     * GET /api/admin/productos/buscar
     */
    public function buscar(Request $request): JsonResponse
    {
        $request->validate([
            'termino' => 'required|string|min:1',
        ]);

        $productos = Producto::with('categoria')
            ->where('active', true)
            ->buscar($request->termino)
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos->map(fn($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'precio' => $p->precio,
                'precio_texto' => '$' . number_format($p->precio, 2),
                'inventario_actual' => $p->inventario_actual,
                'tiene_stock' => $p->inventario_actual > 0,
                'categoria' => $p->categoria ? [
                    'id' => $p->categoria->id,
                    'nombre' => $p->categoria->nombre,
                ] : null,
            ]),
        ]);
    }

    /**
     * Generar código QR para producto
     * 
     * POST /api/admin/productos/{id}/qr
     */
    public function generarQr(int $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404);
        }

        // Generar QR con el código del producto
        $contenidoQr = $producto->codigo;
        $qrDataUri = $this->qrService->generarQr($contenidoQr, 300);

        if (!$qrDataUri) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar código QR',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'producto_id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'qr' => $qrDataUri,
                'contenido_qr' => $contenidoQr,
            ],
        ]);
    }

    /**
     * Formatear producto para respuesta
     */
    private function formatearProducto(Producto $producto): array
    {
        return [
            'id' => $producto->id,
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'foto' => $producto->foto,
            'categoria_id' => $producto->categoria_id,
            'inventario_minimo' => $producto->inventario_minimo,
            'inventario_actual' => $producto->inventario_actual,
            'costo' => $producto->costo,
            'precio' => $producto->precio,
            'precio_texto' => '$' . number_format($producto->precio, 2),
            'active' => $producto->active,
            'stock_bajo' => $producto->stockBajo(),
            'categoria' => $producto->categoria ? [
                'id' => $producto->categoria->id,
                'nombre' => $producto->categoria->nombre,
            ] : null,
        ];
    }

    /**
     * Generar código único para producto
     */
    private function generarCodigo(): string
    {
        do {
            $codigo = 'PROD-' . strtoupper(Str::random(8));
        } while (Producto::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
