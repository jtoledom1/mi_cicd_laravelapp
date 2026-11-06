@extends('layouts.app')

@section('title'){{ $familia ? $familia->nombre . ' | Gastos Hormiga' : 'Tu hogar | Gastos Hormiga' }}@endsection

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/colaborativo.css') }}">
@endpush

@section('content')
    @php
        $dinero = static fn ($n) => '$' . number_format((float) $n, 2);

        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $numeroMes = (int) substr((string) $mes, 5, 2);
        $anioMes = (int) substr((string) $mes, 0, 4);
        $nombreMes = $meses[$numeroMes] ?? 'este mes';
        $nombreMes .= $anioMes > 0 && $anioMes !== (int) now()->format('Y') ? ' de ' . $anioMes : '';

        $personas = $miembros->count();
        $inicial = static fn (string $nombre) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($nombre, 0, 1));

        $miembrosPorId = $miembros->keyBy('id');
        $reparto = collect();

        foreach ($porMiembro as $id => $monto) {
            $persona = $miembrosPorId->get((int) $id);

            $reparto->push([
                'nombre' => $persona?->name ?? 'Alguien que ya no está en el hogar',
                'monto' => (float) $monto,
            ]);
        }

        $reparto = $reparto->sortByDesc('monto')->values();
        $maxReparto = (float) ($reparto->max('monto') ?? 0);
        $maxReparto = $maxReparto > 0 ? $maxReparto : 1;
        $promedio = $personas > 0 ? (float) $totalMes / $personas : 0.0;
    @endphp

    @if ($familia === null)
        <section class="fam-entrada" aria-labelledby="fam-entrada-titulo">
            <header class="fam-cabeza">
                <h1 class="fam-cabeza__titulo" id="fam-entrada-titulo">Tu hogar</h1>
                <p class="fam-cabeza__bajada">
                    Un hogar junta los gastos de la casa (la despensa, el gas, la luz) con quien vive contigo.
                    Cada quien conserva sus gastos personales: aquí solo se suma lo que comparten.
                </p>
            </header>

            @if ($errors->any())
                <div class="errores fam-errores" role="alert">
                    <p class="errores__titulo">
                        @if ($errors->count() === 1)
                            Revisa este campo antes de continuar.
                        @else
                            Revisa estos {{ $errors->count() }} campos antes de continuar.
                        @endif
                    </p>
                    <ul class="errores__lista">
                        @foreach ($errors->all() as $mensaje)
                            <li>{{ $mensaje }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="fam-puertas">
                <section class="fam-puerta" aria-labelledby="fam-crear-titulo">
                    <h2 class="fam-puerta__titulo" id="fam-crear-titulo">Crear un hogar</h2>
                    <p class="fam-puerta__texto">
                        Le pones nombre y quedas como creador. Después compartes el código con quien viva contigo.
                    </p>

                    <form class="fam-form" method="POST" action="{{ route('familia.store') }}">
                        @csrf

                        <div class="campo @error('nombre') campo--mal @enderror">
                            <label class="campo__etiqueta" for="fam-nombre">Nombre del hogar</label>
                            <input
                                class="campo__control"
                                id="fam-nombre"
                                name="nombre"
                                type="text"
                                value="{{ old('nombre') }}"
                                maxlength="80"
                                placeholder="Casa Solar"
                                autocomplete="off"
                                required
                                @error('nombre') aria-invalid="true" aria-describedby="fam-nombre-error" @enderror
                            >
                            @error('nombre')
                                <p class="campo__error" id="fam-nombre-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <button class="boton boton--lleno" type="submit">Crear hogar</button>
                    </form>
                </section>

                <section class="fam-puerta fam-puerta--unirse" aria-labelledby="fam-unirse-titulo">
                    <h2 class="fam-puerta__titulo" id="fam-unirse-titulo">Unirme con un código</h2>
                    <p class="fam-puerta__texto">
                        Te lo pasa quien ya creó el hogar. Son ocho letras y números, sin espacios.
                    </p>

                    <form class="fam-form" method="POST" action="{{ route('familia.unirse') }}">
                        @csrf

                        <div class="campo @error('codigo') campo--mal @enderror">
                            <label class="campo__etiqueta" for="fam-codigo">Código de invitación</label>
                            <input
                                class="campo__control fam-form__codigo"
                                id="fam-codigo"
                                name="codigo"
                                type="text"
                                value="{{ old('codigo') }}"
                                maxlength="12"
                                placeholder="A1B2C3D4"
                                inputmode="text"
                                autocomplete="off"
                                autocapitalize="characters"
                                spellcheck="false"
                                required
                                @error('codigo') aria-invalid="true" aria-describedby="fam-codigo-error" @enderror
                            >
                            @error('codigo')
                                <p class="campo__error" id="fam-codigo-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <button class="boton boton--fantasma" type="submit">Unirme</button>
                    </form>
                </section>
            </div>

            <p class="fam-nota">
                Mientras decides, tus gastos personales siguen su curso:
                <a href="{{ route('gastos.index') }}">ver mis gastos</a>.
            </p>
        </section>
    @else
        <section class="fam-hogar" aria-labelledby="fam-hogar-titulo">
            <header class="fam-cabeza fam-cabeza--hogar">
                <h1 class="fam-cabeza__titulo" id="fam-hogar-titulo">{{ $familia->nombre }}</h1>
                <p class="fam-cabeza__meta">
                    {{ number_format($personas) }} {{ $personas === 1 ? 'persona' : 'personas' }}.
                    @if ($familia->propietario)
                        @if ($familia->propietario_id === auth()->id())
                            Lo creaste tú.
                        @else
                            Lo creó {{ $familia->propietario->name }}.
                        @endif
                    @endif
                </p>
            </header>

            <section class="fam-ficha" aria-labelledby="fam-ficha-titulo">
                <div class="fam-ficha__cuerpo">
                    <h2 class="fam-ficha__rotulo" id="fam-ficha-titulo">Código de invitación</h2>
                    <p class="fam-ficha__codigo" data-codigo-caja><code>{{ $familia->codigo_invitacion }}</code></p>
                    <p class="fam-ficha__nota">Quien tenga este código puede unirse al hogar y anotar los gastos compartidos.</p>
                </div>

                <div class="fam-ficha__lado">
                    <button
                        class="boton fam-boton-claro"
                        type="button"
                        data-copiar
                        data-codigo="{{ $familia->codigo_invitacion }}"
                    >Copiar</button>

                    <p class="fam-ficha__estado" data-copiar-estado role="status" aria-live="polite"></p>

                    @if ($esPropietario)
                        <form
                            class="fam-ficha__regenerar"
                            method="POST"
                            action="{{ route('familia.codigo') }}"
                            onsubmit="return confirm('¿Generar otro código? El actual dejará de funcionar y quien lo tenga ya no podrá unirse.');"
                        >
                            @csrf
                            <button class="fam-enlace-claro" type="submit">Generar otro código</button>
                        </form>
                    @endif
                </div>
            </section>

            <div class="fam-rejilla">
                <div class="fam-columna">
                    <section class="fam-bloque" aria-labelledby="fam-mes-titulo">
                        <div class="fam-bloque__cabeza">
                            <h2 class="titulo-seccion" id="fam-mes-titulo">Lo compartido en {{ $nombreMes }}</h2>
                            <p class="fam-bloque__cuenta">
                                {{ number_format((int) $cantidadMes) }} {{ (int) $cantidadMes === 1 ? 'gasto anotado' : 'gastos anotados' }}
                            </p>
                        </div>

                        <p class="fam-total">{{ $dinero($totalMes) }}</p>

                        <dl class="fam-datos">
                            <div class="dato">
                                <dt class="dato__etiqueta">Promedio por persona</dt>
                                <dd class="dato__valor">{{ $dinero($promedio) }}</dd>
                            </div>
                            <div class="dato">
                                <dt class="dato__etiqueta">Cuentas del hogar</dt>
                                <dd class="dato__valor">{{ number_format($cuentas->count()) }}</dd>
                            </div>
                        </dl>

                        @if ($reparto->isEmpty())
                            <p class="fam-vacio-texto">
                                Nadie ha anotado un gasto compartido en {{ $nombreMes }}. La despensa puede ser el primero.
                            </p>
                        @else
                            <h3 class="fam-subtitulo">Quién anotó qué</h3>
                            <ul class="fam-reparto">
                                @foreach ($reparto as $fila)
                                    @php
                                        $ancho = min(100, max(3, round(($fila['monto'] / $maxReparto) * 100, 1)));
                                        $parte = (float) $totalMes > 0 ? round(($fila['monto'] / (float) $totalMes) * 100) : 0;
                                    @endphp
                                    <li class="fam-reparto__fila">
                                        <span class="fam-reparto__nombre">{{ $fila['nombre'] }}</span>
                                        <span class="fam-reparto__monto">{{ $dinero($fila['monto']) }}</span>
                                        <span class="fam-reparto__parte">{{ $parte }}%</span>
                                        <span class="fam-reparto__barra" aria-hidden="true">
                                            <span class="fam-reparto__relleno" style="width: {{ $ancho }}%"></span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>

                    <section class="fam-bloque" aria-labelledby="fam-gente-titulo">
                        <div class="fam-bloque__cabeza">
                            <h2 class="titulo-seccion" id="fam-gente-titulo">Quiénes están</h2>
                        </div>

                        <ul class="fam-gente">
                            @foreach ($miembros as $miembro)
                                <li class="fam-gente__fila">
                                    <span class="fam-gente__inicial" aria-hidden="true">{{ $inicial($miembro->name) }}</span>
                                    <span class="fam-gente__datos">
                                        <span class="fam-gente__nombre">{{ $miembro->name }}</span>
                                        <span class="fam-gente__correo">{{ $miembro->email }}</span>
                                    </span>
                                    @if ($miembro->id === $familia->propietario_id)
                                        <span class="fam-gente__etiqueta">Creador</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        @if ($personas === 1)
                            <p class="fam-nota">Todavía no hay nadie más en este hogar. Comparte el código de arriba para que se unan.</p>
                        @endif
                    </section>
                </div>

                <aside class="fam-lado">
                    <section class="fam-bloque" aria-labelledby="fam-cuentas-titulo">
                        <div class="fam-bloque__cabeza">
                            <h2 class="titulo-seccion" id="fam-cuentas-titulo">Cuentas del hogar</h2>
                            <a class="fam-enlace" href="{{ route('cuentas.index', ['ambito' => 'familia']) }}">Administrar</a>
                        </div>

                        @if ($cuentas->isEmpty())
                            <p class="fam-nota">
                                El hogar todavía no tiene cuentas.
                                <a href="{{ route('cuentas.index', ['ambito' => 'familia']) }}">Crea la primera</a>.
                            </p>
                        @else
                            <ul class="fam-cuentas">
                                @foreach ($cuentas as $cuenta)
                                    <li class="fam-cuentas__fila">
                                        <span class="fam-cuentas__nombre">{{ $cuenta->nombre }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </aside>
            </div>

            <section class="fam-acciones" aria-label="Acciones del hogar">
                <div class="fam-acciones__principal">
                    <a class="boton boton--lleno" href="{{ route('gastos.index', ['ambito' => 'familia']) }}">
                        Ver los gastos de la familia
                    </a>
                    <p class="fam-acciones__nota">
                        Los gastos del hogar se anotan y se ven aparte de los personales.
                    </p>
                </div>

                <div class="fam-acciones__peligro">
                    @if ($esPropietario)
                        <form
                            class="fam-acciones__form"
                            method="POST"
                            action="{{ route('familia.destroy') }}"
                            onsubmit="return confirm('Eliminar el hogar borra también sus gastos compartidos y sus cuentas. No se puede deshacer. ¿Continuar?');"
                        >
                            @csrf
                            @method('DELETE')
                            <button class="boton boton--peligro" type="submit">Eliminar el hogar</button>
                        </form>

                        <p class="fam-acciones__nota">
                            @if ($personas > 1)
                                Antes de eliminarlo tiene que salir el resto
                                ({{ number_format($personas - 1) }} {{ $personas - 1 === 1 ? 'persona' : 'personas' }}).
                            @else
                                Eres la única persona aquí, así que puedes eliminarlo cuando quieras.
                            @endif
                        </p>
                    @else
                        <form
                            class="fam-acciones__form"
                            method="POST"
                            action="{{ route('familia.salir') }}"
                            onsubmit="return confirm('¿Salir de este hogar? Dejarás de ver los gastos compartidos. Tus gastos personales se quedan contigo.');"
                        >
                            @csrf
                            @method('DELETE')
                            <button class="boton boton--peligro" type="submit">Salir del hogar</button>
                        </form>

                        <p class="fam-acciones__nota">
                            Tus gastos personales no se tocan. Solo dejas de ver lo que se anotó en el hogar.
                        </p>
                    @endif
                </div>
            </section>
        </section>

        @push('scripts')
            <script>
                (function () {
                    var boton = document.querySelector('[data-copiar]');

                    if (!boton) {
                        return;
                    }

                    var estado = document.querySelector('[data-copiar-estado]');
                    var caja = document.querySelector('[data-codigo-caja]');
                    var codigo = boton.getAttribute('data-codigo') || '';
                    var etiqueta = boton.textContent;
                    var reinicio;

                    function avisar(texto) {
                        if (!estado) {
                            return;
                        }

                        estado.textContent = texto;
                        window.clearTimeout(reinicio);
                        reinicio = window.setTimeout(function () {
                            estado.textContent = '';
                        }, 6000);
                    }

                    function copiarSeleccionando() {
                        if (!caja) {
                            return false;
                        }

                        var rango = document.createRange();
                        rango.selectNodeContents(caja);

                        var seleccion = window.getSelection();
                        seleccion.removeAllRanges();
                        seleccion.addRange(rango);

                        try {
                            return document.execCommand('copy');
                        } catch (error) {
                            return false;
                        }
                    }

                    boton.addEventListener('click', function () {
                        function terminar(ok) {
                            if (!ok) {
                                avisar('No se pudo copiar. Selecciona el código y cópialo a mano.');
                                return;
                            }

                            boton.textContent = 'Copiado';
                            avisar('Código copiado. Compártelo con quien viva contigo.');
                            window.setTimeout(function () {
                                boton.textContent = etiqueta;
                            }, 2600);
                        }

                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(codigo).then(function () {
                                terminar(true);
                            }).catch(function () {
                                terminar(copiarSeleccionando());
                            });

                            return;
                        }

                        terminar(copiarSeleccionando());
                    });
                })();
            </script>
        @endpush
    @endif
@endsection
