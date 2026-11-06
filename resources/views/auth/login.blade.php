@extends('layouts.auth')

@section('title', 'Entrar | Gastos Hormiga')

@section('content')
    <h1 class="auth__titulo">Entrar</h1>
    <p class="auth__entrada">Tus gastos hormiga te esperan.</p>

    <form class="auth__form" method="POST" action="{{ route('login') }}">
        @csrf

        @if ($errors->any())
            <div class="errores" role="alert">
                <p class="errores__titulo">
                    @if ($errors->count() === 1)
                        Revisa este campo antes de entrar.
                    @else
                        Revisa estos {{ $errors->count() }} campos antes de entrar.
                    @endif
                </p>
                <ul class="errores__lista">
                    @foreach ($errors->all() as $mensaje)
                        <li>{{ $mensaje }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="campo @error('email') campo--mal @enderror">
            <label class="campo__etiqueta" for="email">Correo</label>
            <input
                class="campo__control"
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="username"
                inputmode="email"
                required
                autofocus
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
            >
            @error('email')
                <p class="campo__error" id="email-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo @error('password') campo--mal @enderror">
            <label class="campo__etiqueta" for="password">Contraseña</label>
            <input
                class="campo__control"
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
            >
            @error('password')
                <p class="campo__error" id="password-error">{{ $message }}</p>
            @enderror
        </div>

        <label class="recordar">
            <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
            <span>Recordarme</span>
        </label>

        <button class="boton boton--lleno auth__enviar" type="submit">Entrar</button>
    </form>

    <p class="auth__alterno">¿No tienes cuenta? <a href="{{ route('register') }}">Crear una</a></p>
@endsection
