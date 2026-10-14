<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Nuevo id de sesión tras el login (evita fijación de sesión).
        $request->session()->regenerate();

        // Siempre al dashboard: no se usa redirect()->intended(), que llevaría a la última
        // página pedida antes del login (por ejemplo, la que dejó abierta el usuario anterior).
        $request->session()->forget('url.intended');

        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // Invalida la sesión actual y genera un nuevo token CSRF.
        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Pide al navegador borrar su caché de este sitio: "Atrás" no mostrará páginas del usuario anterior.
        return redirect()->route('login')->header('Clear-Site-Data', '"cache"');
    }
}
