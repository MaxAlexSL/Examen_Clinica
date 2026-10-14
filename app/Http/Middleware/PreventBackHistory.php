<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impide que el navegador guarde en caché las páginas autenticadas.
 * Así, tras cerrar sesión, el botón "Atrás" no muestra datos del usuario anterior:
 * el navegador vuelve a pedir la página y el servidor redirige al login.
 */
class PreventBackHistory
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
