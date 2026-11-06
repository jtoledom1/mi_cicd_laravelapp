<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Muestra el formulario de entrada.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Autentica al usuario y lo manda a donde iba.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('gastos.index', absolute: false));
    }

    /**
     * Cierra la sesión.
     */
    public function destroy(Request $request): RedirectResponse
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
