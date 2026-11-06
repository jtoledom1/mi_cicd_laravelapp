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
    @stack('estilos')
</head>
<body>
    <a class="salto" href="#contenido">Saltar al contenido</a>

    @php
        $enGastos = request()->routeIs('gastos.*');
        $enMensual = request()->routeIs('resumen.mensual');
        $enAnual = request()->routeIs('resumen.anual');
        $enFamilia = request()->routeIs('familia.*');
        $enCuentas = request()->routeIs('cuentas.*');
        $usuario = auth()->user();
    @endphp

    <header class="cabecera">
        <div class="envoltura cabecera__interior">
            <a class="marca" href="{{ auth()->check() ? route('gastos.index') : route('login') }}">
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

            @auth
                <nav class="nav-principal" aria-label="Navegación principal">
                    <a class="nav-principal__enlace{{ $enGastos ? ' nav-principal__enlace--activo' : '' }}"
                        href="{{ route('gastos.index') }}"
                        @if ($enGastos) aria-current="page" @endif>Gastos</a>
                    <a class="nav-principal__enlace{{ $enMensual ? ' nav-principal__enlace--activo' : '' }}"
                        href="{{ route('resumen.mensual') }}"
                        @if ($enMensual) aria-current="page" @endif>Mensual</a>
                    <a class="nav-principal__enlace{{ $enAnual ? ' nav-principal__enlace--activo' : '' }}"
                        href="{{ route('resumen.anual') }}"
                        @if ($enAnual) aria-current="page" @endif>Anual</a>
                    <a class="nav-principal__enlace{{ $enFamilia ? ' nav-principal__enlace--activo' : '' }}"
                        href="{{ route('familia.index') }}"
                        @if ($enFamilia) aria-current="page" @endif>Familia</a>
                    <a class="nav-principal__enlace{{ $enCuentas ? ' nav-principal__enlace--activo' : '' }}"
                        href="{{ route('cuentas.index') }}"
                        @if ($enCuentas) aria-current="page" @endif>Cuentas</a>
                </nav>

                <div class="cabecera__nav">
                    <a class="boton boton--lleno" href="{{ route('gastos.create') }}"><span class="cabecera__cta-texto">Anotar<span class="cabecera__cta-largo"> un gasto</span></span></a>

                    <details class="usuario">
                        <summary class="usuario__disparador" aria-label="Menú de cuenta de {{ $usuario->name }}">
                            <span class="usuario__inicial" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($usuario->name, 0, 1)) }}</span>
                            <span class="usuario__nombre">{{ $usuario->name }}</span>
                            <svg class="usuario__chevron" viewBox="0 0 12 12" width="12" height="12" aria-hidden="true" focusable="false">
                                <path d="m3 4.5 3 3 3-3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </summary>

                        <div class="usuario__panel">
                            <p class="usuario__nombre">{{ $usuario->name }}</p>
                            <p class="usuario__correo">{{ $usuario->email }}</p>

                            <form class="usuario__salir" method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="boton boton--fantasma" type="submit">Cerrar sesión</button>
                            </form>
                        </div>
                    </details>
                </div>
            @endauth
        </div>
    </header>

    <main class="envoltura principal" id="contenido" tabindex="-1">
        @if (session('error'))
            <div class="aviso aviso--error errores" role="alert">
                <svg class="aviso__icono" viewBox="0 0 20 20" width="18" height="18" aria-hidden="true" focusable="false">
                    <circle cx="10" cy="10" r="7.4" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                    <path d="M10 5.8v5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                    <circle cx="10" cy="14.1" r="1" fill="currentColor"></circle>
                </svg>
                <p class="aviso__texto">{{ session('error') }}</p>
            </div>
        @endif

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

    @auth
        <script>
            (function () {
                var menu = document.querySelector('.usuario');

                if (!menu) {
                    return;
                }

                document.addEventListener('click', function (evento) {
                    if (menu.open && !menu.contains(evento.target)) {
                        menu.open = false;
                    }
                });

                document.addEventListener('keydown', function (evento) {
                    if (evento.key !== 'Escape' || !menu.open) {
                        return;
                    }

                    menu.open = false;
                    var disparador = menu.querySelector('.usuario__disparador');

                    if (disparador) {
                        disparador.focus();
                    }
                });
            })();
        </script>
    @endauth

    @stack('scripts')
</body>
</html>
