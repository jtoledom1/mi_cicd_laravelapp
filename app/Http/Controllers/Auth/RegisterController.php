<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Muestra el formulario de registro.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Crea la cuenta y deja al usuario dentro con la sesión iniciada.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()
            ->route('gastos.index')
            ->with('status', 'Tu cuenta está lista. Empieza anotando el primer gasto.');
    }
}
