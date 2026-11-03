<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#0E3B36">
    <meta name="description" content="Gastos Hormiga: un registro personal de los gastos chicos que se acumulan sin que los notes.">
    <title>@yield('title', 'Gastos Hormiga')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:ital,wght@0,400;0,700;1,400&amp;family=Bricolage+Grotesque:opsz,wght@12..96,400..800&amp;display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <a class="salto" href="#contenido">Saltar al contenido</a>

    <header class="cabecera">
        <div class="envoltura cabecera__interior">
            <a class="marca" href="{{ route('gastos.index') }}">
                <svg class="marca__icono" viewBox="0 0 24 24" width="26" height="26" aria-hidden="true" focusable="false">
                    <g fill="currentColor">
                        <circle cx="17.6" cy="12" r="2"></circle>
                        <ellipse cx="12.2" cy="12" rx="2.1" ry="1.9"></ellipse>
                        <ellipse cx="6.6" cy="12" rx="3" ry="2.5"></ellipse>
                    </g>
                    <g fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                        <path d="M16.6 10.4 18.4 7.6M18.9 12h2.2M16.6 13.6 18.4 16.4M11.4 10.4 10.4 7.4M11.4 13.6 10.4 16.6M12.9 10.4 14 7.5M12.9 13.6 14 16.5"></path>
                    </g>
                </svg>
                <span class="marca__texto">Gastos Hormiga</span>
            </a>

            @unless (request()->routeIs('gastos.create'))
                <nav class="cabecera__nav" aria-label="Navegación principal">
                    <a class="boton boton--lleno" href="{{ route('gastos.create') }}">Anotar un gasto</a>
                </nav>
            @endunless
        </div>
    </header>

    <main class="envoltura principal" id="contenido" tabindex="-1">
        @if (session('status'))
            <div class="aviso" role="status">
                <svg class="aviso__icono" viewBox="0 0 20 20" width="18" height="18" aria-hidden="true" focusable="false">
                    <path d="m4 10.5 4 4 8-9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <p class="aviso__texto">{{ session('status') }}</p>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
