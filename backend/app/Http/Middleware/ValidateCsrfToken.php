<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;

class ValidateCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Excluir todas las rutas API de la validación CSRF
        'api/*',
        '*/api/*',
        'backend/public/api/*',
        '*/backend/public/api/*',
    ];

    /**
     * Handle an incoming request.
     * Excluye automáticamente las peticiones API (JSON) de la validación CSRF
     */
    public function handle($request, Closure $next)
    {
        // Si la petición espera JSON o tiene header de API, omitir CSRF
        if ($this->isApiRequest($request)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * Determina si es una petición de API
     */
    protected function isApiRequest(Request $request): bool
    {
        // Verificar si acepta JSON
        if ($request->expectsJson()) {
            return true;
        }

        // Verificar si la URL contiene 'api'
        if (str_contains($request->path(), 'api')) {
            return true;
        }

        // Verificar el header Accept
        $accept = $request->header('Accept', '');
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        // Verificar si tiene header Authorization Bearer (token API)
        $auth = $request->header('Authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            return true;
        }

        return false;
    }
}

