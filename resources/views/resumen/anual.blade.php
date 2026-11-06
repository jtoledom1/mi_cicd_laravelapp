@extends('layouts.app')

@section('title', 'Resumen de ' . $anio . ' | Gastos Hormiga')

@push('estilos')
    <link rel="stylesheet" href="{{ asset('css/resumen.css') }}">
@endpush

@section('content')
    @php
        $dinero = static fn ($n) => '$' . number_format((float) $n, 2);

        // Mismos colores que el rastro del index: la categoría tiene el mismo
        // color en todo el proyecto.
        $paleta = ['#0E3B36', '#B5134F', '#B98A1F', '#3F6F63', '#8A5A2B', '#5B4B8A'];
        $colorCategoria = static fn (string $categoria) => $paleta[abs((int) crc32($categoria)) % count($paleta)];

        $variacionPct = $variacion !== null ? (int) round(abs((float) $variacion)) : null;
        $maxCategoria = $porCategoria->isNotEmpty() ? (float) $porCategoria->max('total') : 0.0;
        $hayReparto = $porCategoria->isNotEmpty();

        // Conserva el espacio (personal o familia) en todos los enlaces.
        $conAmb = static fn (array $extra = []) => array_merge($ambito->parametros(), $extra);
        $tieneHogar = auth()->user()->familia() !== null;
    @endphp

    <header class="rm-cabeza">
        <div class="rm-cabeza__nav">
            <form class="rm-saltar" method="GET" action="{{ route('resumen.anual') }}">
                <label class="rm-saltar__etiqueta" for="anio">Año</label>
                <select class="rm-saltar__control" id="anio" name="anio">
                    @foreach ($anios as $anioOpcion)
                        <option value="{{ $anioOpcion }}" @selected((int) $anioOpcion === (int) $anio)>{{ $anioOpcion }}</option>
                    @endforeach
                </select>
                @if ($ambito->esFamilia())
                    <input type="hidden" name="ambito" value="familia">
                @endif
                <button class="boton boton--fantasma" type="submit">Ver</button>
            </form>

            @if ($tieneHogar)
                <nav class="rm-cabeza__espacio" aria-label="Espacio de los gastos">
                    <a
                        class="rm-nav-enlace{{ $ambito->esFamilia() ? '' : ' rm-nav-enlace--activo' }}"
                        href="{{ route('resumen.anual', ['anio' => $anio]) }}"
                        @if (! $ambito->esFamilia()) aria-current="page" @endif
                    >Personal</a>
                    <a
                        class="rm-nav-enlace{{ $ambito->esFamilia() ? ' rm-nav-enlace--activo' : '' }}"
                        href="{{ route('resumen.anual', ['anio' => $anio, 'ambito' => 'familia']) }}"
                        @if ($ambito->esFamilia()) aria-current="page" @endif
                    >{{ $ambito->esFamilia() ? $ambito->nombre() : 'Familia' }}</a>
                </nav>
            @endif

            <nav class="rm-cabeza__paso" aria-label="Cambiar de año">
                <a
                    class="rm-nav-enlace"
                    rel="prev"
                    href="{{ route('resumen.anual', $conAmb(['anio' => $anioAnterior])) }}"
                    aria-label="Ver el resumen de {{ $anioAnterior }}"
                >
                    <svg viewBox="0 0 16 16" width="13" height="13" aria-hidden="true" focusable="false">
                        <path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    {{ $anioAnterior }}
                </a>

                <a
                    class="rm-nav-enlace"
                    rel="next"
                    href="{{ route('resumen.anual', $conAmb(['anio' => $anioSiguiente])) }}"
                    aria-label="Ver el resumen de {{ $anioSiguiente }}"
                >
                    {{ $anioSiguiente }}
                    <svg viewBox="0 0 16 16" width="13" height="13" aria-hidden="true" focusable="false">
                        <path d="m6 3 5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </a>
            </nav>
        </div>

        <h1 class="rm-cabeza__titulo rm-cabeza__titulo--largo">El año {{ $anio }} en gastos hormiga</h1>
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

            <h2 class="vacio__titulo" id="vacio-titulo">El año {{ $anio }} está en blanco</h2>
            <p class="vacio__texto">
                No hay gastos anotados en {{ $anio }}. El primer café del día ya deja rastro,
                y con doce meses por delante alcanza para ver el ritmo completo.
            </p>

            <div class="rm-vacio__acciones">
                <a class="boton boton--lleno" href="{{ route('gastos.create') }}">Anotar un gasto</a>
                <a class="boton boton--fantasma" href="{{ route('resumen.mensual', $conAmb()) }}">Ver el mes en curso</a>
            </div>
        </section>
    @else
        <section class="rm-total" aria-labelledby="total-titulo">
            <h2 class="rm-total__rotulo" id="total-titulo">Total del año</h2>
            <p class="resumen__total">{{ $dinero($total) }}</p>

            @if ($variacion !== null && $variacionPct > 0)
                <p class="resumen__frase"><strong>Un {{ $variacionPct }} % {{ (float) $variacion > 0 ? 'más' : 'menos' }}</strong> que en {{ $anioAnterior }}.</p>
            @elseif ($variacion !== null)
                <p class="resumen__frase">Casi lo mismo que en {{ $anioAnterior }}.</p>
            @elseif ((float) $totalAnterior <= 0)
                <p class="resumen__frase">No hay registro del año anterior.</p>
            @endif

            <dl class="resumen__datos">
                <div class="dato">
                    <dt class="dato__etiqueta">Gastos anotados</dt>
                    <dd class="dato__valor">{{ number_format((int) $cantidad) }}</dd>
                </div>
                <div class="dato">
                    <dt class="dato__etiqueta">Promedio mensual</dt>
                    <dd class="dato__valor">
                        {{ $dinero($promedioMensual) }}
                        <span class="rm-dato__nota">
                            sobre {{ number_format((int) $mesesConDatos) }} {{ $mesesConDatos === 1 ? 'mes con datos' : 'meses con datos' }}
                        </span>
                    </dd>
                </div>
                <div class="dato">
                    <dt class="dato__etiqueta">Mejor mes</dt>
                    <dd class="dato__valor">
                        @if ($mejorMes !== null)
                            {{ $dinero($maximoMes) }}
                            <span class="rm-dato__nota">en {{ $nombresMeses[$mejorMes] }}</span>
                        @else
                            —
                            <span class="rm-dato__nota">sin gastos</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rm-seccion" aria-labelledby="meses-titulo">
            <div class="rm-seccion__cabeza">
                <h2 class="titulo-seccion" id="meses-titulo">Los doce meses</h2>
                <p class="rm-seccion__nota">
                    Una barra por mes, medida contra el más caro ({{ $dinero($maximoMes) }}).
                    La de jamaica es la de ese mes; el que está en curso lleva su marca.
                </p>
            </div>

            <div class="rm-meses">
                @foreach ($nombresMeses as $numeroMes => $nombreDelMes)
                    @php
                        $montoMes = (float) ($porMes[$numeroMes] ?? 0);
                        $parteMes = $maximoMes > 0 ? round($montoMes / (float) $maximoMes * 100, 2) : 0;
                        $esVacio = $montoMes <= 0;
                        $esTope = $mejorMes !== null && (int) $mejorMes === (int) $numeroMes;
                        $esCurso = $mesActual !== null && (int) $mesActual === (int) $numeroMes;
                        $claveMes = sprintf('%04d-%02d', (int) $anio, (int) $numeroMes);
                    @endphp

                    <a
                        class="rm-mes{{ $esVacio ? ' rm-mes--vacio' : '' }}{{ $esTope && ! $esVacio ? ' rm-mes--tope' : '' }}{{ $esCurso ? ' rm-mes--curso' : '' }}"
                        href="{{ route('resumen.mensual', $conAmb(['mes' => $claveMes])) }}"
                        aria-label="{{ $nombreDelMes }} de {{ $anio }}: {{ $esVacio ? 'sin gastos' : $dinero($montoMes) }}. Ver el resumen"
                    >
                        <span class="rm-mes__nombre">
                            {{ ucfirst($nombreDelMes) }}@if ($esCurso)<span class="rm-mes__marca">en curso</span>@endif
                        </span>

                        <span class="rm-mes__pista" aria-hidden="true">
                            @if (! $esVacio)
                                <span class="rm-mes__relleno" style="--parte: {{ $parteMes }}%; --i: {{ $loop->index }};"></span>
                            @endif
                        </span>

                        <span class="rm-mes__monto">{{ $esVacio ? '—' : $dinero($montoMes) }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        @if ($hayReparto)
            <section class="rm-seccion" aria-labelledby="reparto-titulo">
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
    @endif
@endsection
