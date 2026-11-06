@extends('layouts.app')

@section('title', 'Resumen de ' . $nombreMes . ' | Gastos Hormiga')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/resumen.css') }}">
@endpush

@section('content')
    @php
        $dinero = static fn ($n) => '$' . number_format((float) $n, 2);

        $mesesCortos = [];
        foreach ($nombresMeses as $numero => $nombre) {
            $mesesCortos[(int) $numero] = \Illuminate\Support\Str::substr($nombre, 0, 3);
        }

        // Mismos colores que el rastro del index: la categoría tiene el mismo
        // color en todo el proyecto.
        $paleta = ['#0E3B36', '#B5134F', '#B98A1F', '#3F6F63', '#8A5A2B', '#5B4B8A'];
        $colorCategoria = static fn (string $categoria) => $paleta[abs((int) crc32($categoria)) % count($paleta)];

        $mesAnteriorNumero = (int) substr($mesAnterior, 5, 2);
        $mesSiguienteNumero = (int) substr($mesSiguiente, 5, 2);
        $mesAnteriorAnio = (int) substr($mesAnterior, 0, 4);
        $mesSiguienteAnio = (int) substr($mesSiguiente, 0, 4);
        $mesAnteriorNombre = $nombresMeses[$mesAnteriorNumero] ?? '';
        $mesSiguienteNombre = $nombresMeses[$mesSiguienteNumero] ?? '';

        $variacionPct = $variacion !== null ? (int) round(abs((float) $variacion)) : null;
        $diasBase = $esMesActual ? max(1, (int) now()->day) : (int) $diasDelMes;
        $diasConGasto = count(array_filter($porDia, static fn ($valor) => (float) $valor > 0));

        $etiquetasDia = array_values(array_unique([1, 10, 20, (int) $diasDelMes]));
        sort($etiquetasDia);

        $hayReparto = $porCategoria->isNotEmpty();
        $hayTop = $topGastos->isNotEmpty();
        $maxCategoria = $hayReparto ? (float) $porCategoria->max('total') : 0.0;

        // Conserva el espacio (personal o familia) en todos los enlaces.
        $conAmb = static fn (array $extra = []) => array_merge($ambito->parametros(), $extra);
        $tieneHogar = auth()->user()->familia() !== null;
    @endphp

    <header class="rm-cabeza">
        <div class="rm-cabeza__nav">
            <a class="rm-nav-enlace" href="{{ route('gastos.index', $conAmb(['mes' => $mes])) }}">
                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false">
                    <path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Volver a mis gastos
            </a>

            @if ($tieneHogar)
                <nav class="rm-cabeza__espacio" aria-label="Espacio de los gastos">
                    <a
                        class="rm-nav-enlace{{ $ambito->esFamilia() ? '' : ' rm-nav-enlace--activo' }}"
                        href="{{ route('resumen.mensual', ['mes' => $mes]) }}"
                        @if (! $ambito->esFamilia()) aria-current="page" @endif
                    >Personal</a>
                    <a
                        class="rm-nav-enlace{{ $ambito->esFamilia() ? ' rm-nav-enlace--activo' : '' }}"
                        href="{{ route('resumen.mensual', ['mes' => $mes, 'ambito' => 'familia']) }}"
                        @if ($ambito->esFamilia()) aria-current="page" @endif
                    >{{ $ambito->esFamilia() ? $ambito->nombre() : 'Familia' }}</a>
                </nav>
            @endif
            <nav class="rm-cabeza__paso" aria-label="Cambiar de mes">
                <a
                    class="rm-nav-enlace"
                    rel="prev"
                    href="{{ route('resumen.mensual', $conAmb(['mes' => $mesAnterior])) }}"
                    aria-label="Ver el resumen de {{ $mesAnteriorNombre }} de {{ $mesAnteriorAnio }}"
                >
                    <svg viewBox="0 0 16 16" width="13" height="13" aria-hidden="true" focusable="false">
                        <path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    {{ ucfirst($mesAnteriorNombre) }}{{ $mesAnteriorAnio !== $anio ? ' de ' . $mesAnteriorAnio : '' }}
                </a>

                <a
                    class="rm-nav-enlace"
                    rel="next"
                    href="{{ route('resumen.mensual', $conAmb(['mes' => $mesSiguiente])) }}"
                    aria-label="Ver el resumen de {{ $mesSiguienteNombre }} de {{ $mesSiguienteAnio }}"
                >
                    {{ ucfirst($mesSiguienteNombre) }}{{ $mesSiguienteAnio !== $anio ? ' de ' . $mesSiguienteAnio : '' }}
                    <svg viewBox="0 0 16 16" width="13" height="13" aria-hidden="true" focusable="false">
                        <path d="m6 3 5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </a>
            </nav>
        </div>

        <h1 class="rm-cabeza__titulo">{{ ucfirst($nombreMes) }}</h1>
    </header>

    @if ((float) $total <= 0)
        <section class="vacio" aria-labelledby="vacio-titulo">
            <svg class="vacio__icono" width="148" height="32" viewBox="0 0 148 32" aria-hidden="true" focusable="false">
                <g fill="currentColor">
                    <rect x="0" y="19" width="10" height="13" rx="1"></rect>
                    <rect x="14" y="11" width="22" height="21" rx="1"></rect>
                    <rect x="40" y="17" width="14" height="15" rx="1"></rect>
                    <rect x="58" y="6" width="30" height="26" rx="1"></rect>
                    <rect x="92" y="15" width="18" height="17" rx="1"></rect>
                    <rect x="114" y="21" width="8" height="11" rx="1"></rect>
                    <path d="M128 29h20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="2 4" stroke-linecap="round"></path>
                </g>
            </svg>

            <h2 class="vacio__titulo" id="vacio-titulo">Aún no hay nada anotado</h2>
            <p class="vacio__texto">
                No hay gastos registrados en {{ $nombreMes }}. Un café, el camión, la botana de la tarde:
                cualquiera deja la primera huella.
            </p>

            <div class="rm-vacio__acciones">
                <a class="boton boton--lleno" href="{{ route('gastos.create') }}">Anotar un gasto</a>
                <a class="boton boton--fantasma" href="{{ route('resumen.mensual', ['mes' => $mesAnterior]) }}">
                    Ver {{ ucfirst($mesAnteriorNombre) }}
                </a>
            </div>
        </section>
    @else
        <section class="rm-total" aria-labelledby="total-titulo">
            <h2 class="rm-total__rotulo" id="total-titulo">Total del mes</h2>
            <p class="resumen__total">{{ $dinero($total) }}</p>

            @if ($variacion !== null && $variacionPct > 0)
                <p class="resumen__frase"><strong>Un {{ $variacionPct }} % {{ (float) $variacion > 0 ? 'más' : 'menos' }}</strong> que en {{ $mesAnteriorNombre }}.</p>
            @elseif ($variacion !== null)
                <p class="resumen__frase">Casi lo mismo que en {{ $mesAnteriorNombre }}.</p>
            @elseif ((float) $totalAnterior <= 0)
                <p class="resumen__frase">El mes pasado no anotaste nada.</p>
            @endif

            <dl class="resumen__datos">
                <div class="dato">
                    <dt class="dato__etiqueta">Gastos anotados</dt>
                    <dd class="dato__valor">{{ number_format((int) $cantidad) }}</dd>
                </div>
                <div class="dato">
                    <dt class="dato__etiqueta">Promedio diario</dt>
                    <dd class="dato__valor">
                        {{ $dinero($promedioDiario) }}
                        <span class="rm-dato__nota">
                            sobre {{ number_format($diasBase) }} {{ $diasBase === 1 ? 'día' : 'días' }}{{ $esMesActual ? ' del mes en curso' : '' }}
                        </span>
                    </dd>
                </div>
                <div class="dato">
                    <dt class="dato__etiqueta">Día más caro</dt>
                    <dd class="dato__valor">
                        @if ($diaMasCaro !== null)
                            {{ $dinero($maximoDia) }}
                            <span class="rm-dato__nota">el {{ $diaMasCaro }} de {{ $nombresMeses[$numeroMes] }}</span>
                        @else
                            —
                            <span class="rm-dato__nota">sin gastos</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rm-seccion" aria-labelledby="ritmo-titulo">
            <div class="rm-seccion__cabeza">
                <h2 class="titulo-seccion" id="ritmo-titulo">Ritmo del mes</h2>
                <p class="rm-seccion__nota">
                    Una barra por día, con la altura de lo que costó. La de jamaica es la del día más caro;
                    los días en blanco apenas dejan una marca.
                </p>
            </div>

            <figure class="rm-ritmo" style="--dias: {{ (int) $diasDelMes }}">
                <div class="rm-ritmo__grafico" aria-hidden="true">
                    @foreach ($porDia as $dia => $montoDia)
                        @php
                            $alto = $maximoDia > 0 ? round(((float) $montoDia / (float) $maximoDia) * 100, 2) : 0;
                            $esTope = $diaMasCaro !== null && (int) $dia === (int) $diaMasCaro;
                        @endphp
                        <span class="rm-ritmo__dia{{ $esTope ? ' rm-ritmo__dia--tope' : '' }}">
                            <span
                                class="rm-ritmo__barra{{ (float) $montoDia <= 0 ? ' rm-ritmo__barra--vacia' : '' }}"
                                style="--alto: {{ $alto }}%; --i: {{ $loop->index }};"
                            ></span>
                        </span>
                    @endforeach
                </div>

                <div class="rm-ritmo__eje" aria-hidden="true">
                    @foreach ($etiquetasDia as $diaEtiqueta)
                        <span
                            class="rm-ritmo__etiqueta {{ $loop->first ? 'rm-ritmo__etiqueta--inicio' : ($loop->last ? 'rm-ritmo__etiqueta--fin' : 'rm-ritmo__etiqueta--medio') }}"
                            style="grid-column: {{ $diaEtiqueta }};"
                        >{{ $diaEtiqueta }}</span>
                    @endforeach
                </div>

                <figcaption class="rm-ritmo__nota">
                    @if ($diaMasCaro !== null)
                        El día <strong>{{ $diaMasCaro }}</strong> fue el más caro:
                        <strong>{{ $dinero($maximoDia) }}</strong>.
                    @endif
                    @if ($diasConGasto >= (int) $diasDelMes)
                        Hubo gasto los {{ $diasDelMes }} días.
                    @else
                        Hubo gasto en {{ $diasConGasto }} de {{ $diasDelMes }} días.
                    @endif

                    <table class="oculto">
                        <caption>Gasto por día en {{ $nombreMes }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Día</th>
                                <th scope="col">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($porDia as $dia => $montoDia)
                                <tr>
                                    <th scope="row">{{ $dia }}</th>
                                    <td>{{ $dinero($montoDia) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </figcaption>
            </figure>
        </section>

        @if ($hayReparto || $hayTop)
            <div class="rm-duo{{ $hayReparto && $hayTop ? '' : ' rm-duo--solo' }}">
                @if ($hayReparto)
                    <section class="rm-seccion rm-seccion--duo" aria-labelledby="reparto-titulo">
                        <h2 class="titulo-seccion" id="reparto-titulo">En qué se fue</h2>

                        <ul class="rm-cat">
                            @foreach ($porCategoria as $reparto)
                                @php
                                    $anchoBarra = $maxCategoria > 0
                                        ? max(2, round(((float) $reparto->total / $maxCategoria) * 100, 1))
                                        : 0;
                                    $parte = (int) round((float) $reparto->parte);
                                @endphp
                                <li class="rm-cat__fila">
                                    <span class="rm-cat__punto" style="background: {{ $colorCategoria((string) $reparto->categoria) }}" aria-hidden="true"></span>
                                    <span class="rm-cat__nombre">{{ $reparto->categoria }}</span>
                                    <span class="rm-cat__monto">
                                        {{ $dinero($reparto->total) }}
                                        <span class="rm-cat__parte">{{ $parte > 0 ? $parte : '<1' }} %</span>
                                    </span>
                                    <span class="rm-cat__barra" aria-hidden="true">
                                        <span
                                            class="rm-cat__relleno"
                                            style="--parte: {{ $anchoBarra }}%; background: {{ $colorCategoria((string) $reparto->categoria) }}"
                                        ></span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($hayTop)
                    <section class="rm-seccion rm-seccion--duo rm-duo__bloque--der" aria-labelledby="top-titulo">
                        <h2 class="titulo-seccion" id="top-titulo">Los cinco más caros</h2>

                        <ol class="rm-top">
                            @foreach ($topGastos as $gasto)
                                @php
                                    $fechaGasto = $gasto->fecha instanceof \DateTimeInterface
                                        ? $gasto->fecha
                                        : rescue(static fn () => \Illuminate\Support\Carbon::parse((string) $gasto->fecha), null, false);
                                @endphp
                                <li>
                                    <a class="rm-top__fila" href="{{ route('gastos.show', $gasto) }}">
                                        <span class="rm-top__cuerpo">
                                            <span class="rm-top__desc">{{ $gasto->descripcion }}</span>
                                            <span class="rm-top__meta">
                                                {{ $gasto->categoria }}@if ($fechaGasto), <time datetime="{{ $fechaGasto->format('Y-m-d') }}">{{ $fechaGasto->format('j') }} {{ $mesesCortos[(int) $fechaGasto->format('n')] }}</time>@endif
                                            </span>
                                        </span>
                                        <span class="rm-top__monto">{{ $dinero($gasto->monto) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </div>
        @endif
    @endif
@endsection
