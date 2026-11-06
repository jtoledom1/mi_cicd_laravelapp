@extends('layouts.auth')

@section('title', 'Crear cuenta | Gastos Hormiga')

@section('content')
    <h1 class="auth__titulo">Crear cuenta</h1>
    <p class="auth__entrada">Un lugar para tus gastos chicos.</p>

    <form class="auth__form" method="POST" action="{{ route('register') }}">
        @csrf

        @if ($errors->any())
            <div class="errores" role="alert">
                <p class="errores__titulo">
                    @if ($errors->count() === 1)
                        Revisa este campo antes de guardar.
                    @else
                        Revisa estos {{ $errors->count() }} campos antes de guardar.
                    @endif
                </p>
                <ul class="errores__lista">
                    @foreach ($errors->all() as $mensaje)
                        <li>{{ $mensaje }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="campo @error('name') campo--mal @enderror">
            <label class="campo__etiqueta" for="name">Nombre</label>
            <input
                class="campo__control"
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                maxlength="80"
                autocomplete="name"
                required
                autofocus
                @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
            >
            @error('name')
                <p class="campo__error" id="name-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo @error('email') campo--mal @enderror">
            <label class="campo__etiqueta" for="email">Correo</label>
            <input
                class="campo__control"
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                inputmode="email"
                required
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
                autocomplete="new-password"
                aria-describedby="password-ayuda{{ $errors->has('password') ? ' password-error' : '' }}"
                required
                @error('password') aria-invalid="true" @enderror
            >
            <p class="campo__ayuda" id="password-ayuda">Mínimo 8 caracteres.</p>
            @error('password')
                <p class="campo__error" id="password-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo @error('password_confirmation') campo--mal @enderror">
            <label class="campo__etiqueta" for="password_confirmation">Repite la contraseña</label>
            <input
                class="campo__control"
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                required
                @error('password_confirmation') aria-invalid="true" aria-describedby="password-confirmation-error" @enderror
            >
            @error('password_confirmation')
                <p class="campo__error" id="password-confirmation-error">{{ $message }}</p>
            @enderror
        </div>

        <button class="boton boton--lleno auth__enviar" type="submit">Crear cuenta</button>
    </form>

    <p class="auth__alterno">¿Ya tienes cuenta? <a href="{{ route('login') }}">Entrar</a></p>
@endsection
