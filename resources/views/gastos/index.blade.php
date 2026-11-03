@extends('layouts.app')

@section('title', 'Este mes | Gastos Hormiga')

@section('content')
    @php
        $dinero = static fn ($n) => '$' . number_format((float) $n, 2);

        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];
        $mesesCortos = [
            1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
            7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
        ];

        $fechaMes = rescue(
            static fn () => \Illuminate\Support\Carbon::createFromFormat('!Y-m', (string) $mes),
            now(),
            false
        ) ?: now();

        $nombreMes = $meses[(int) $fechaMes->month] . ' de ' . $fechaMes->year;
        $diasMes = (int) $fechaMes->daysInMonth;
        $mesActual = now()->format('Y-m');

        $cafes = (int) floor((float) $totalMes / 45);
        $pasajes = (int) floor((float) $totalMes / 12);

        $paleta = ['#0E3B36', '#B5134F', '#B98A1F', '#3F6F63', '#8A5A2B', '#5B4B8A'];
        $colorCategoria = static fn (string $nombre) => $paleta[abs((int) crc32($nombre)) % count($paleta)];

        $rastro = $gastos->getCollection()
            ->sortBy(static fn ($g) => $g->fecha instanceof \DateTimeInterface ? $g->fecha->format('Y-m-d') : (string) $g->fecha)
            ->values();

        $gastosPorCategoria = collect($porCategoria)->sortDesc();
        $maxCategoria = (float) ($gastosPorCategoria->max() ?? 0);
        $totalCategorias = (float) $gastosPorCategoria->sum();

        $paginador = $gastos->appends(array_filter(
            ['mes' => $mes, 'categoria' => $categoria],
            static fn ($valor) => $valor !== null && $valor !== ''
        ));

        $mesCompleto = $gastos->total() <= $rastro->count();
    @endphp

    <form class="filtros" method="GET" action="{{ route('gastos.index') }}">
        <div class="filtros__campo">
            <label class="filtros__etiqueta" for="filtro-mes">Mes</label>
            <input class="filtros__control" id="filtro-mes" name="mes" type="month" value="{{ $fechaMes->format('Y-m') }}" required>
        </div>

        <div class="filtros__campo">
            <label class="filtros__etiqueta" for="filtro-categoria">Categoría</label>
            <select class="filtros__control" id="filtro-categoria" name="categoria">
                <option value="">Todas</option>
                @foreach ($categorias as $opcion)
                    <option value="{{ $opcion }}" @selected((string) $categoria === (string) $opcion)>{{ $opcion }}</option>
                @endforeach
            </select>
        </div>

        <div class="filtros__acciones">
            <button class="boton boton--fantasma" type="submit">Filtrar</button>
            @if ($categoria || $mes !== $mesActual)
                <a class="filtros__limpiar" href="{{ route('gastos.index') }}">Quitar filtros</a>
            @endif
        </div>
    </form>

    <section class="resumen" aria-labelledby="resumen-titulo">
        <h1 class="resumen__titulo" id="resumen-titulo">Lo que llevas gastado en {{ $nombreMes }}</h1>
        <p class="resumen__total">{{ $dinero($totalMes) }}</p>

        @if ((float) $totalMes <= 0)
            <p class="resumen__frase">Todavía no hay gastos este mes. El primer café ya cuenta.</p>
        @elseif ($cafes >= 2)
            <p class="resumen__frase">
                Equivale a <strong>{{ number_format($cafes) }} cafés de $45</strong>, o a
                {{ number_format($pasajes) }} pasajes de camión de $12.
            </p>
        @elseif ($cafes === 1)
            <p class="resumen__frase">
                Equivale a <strong>un café de $45</strong>, o a {{ number_format($pasajes) }} pasajes de camión de $12.
            </p>
        @elseif ($pasajes >= 2)
            <p class="resumen__frase">
                Alcanza para {{ number_format($pasajes) }} pasajes de camión de $12.
            </p>
        @elseif ($pasajes === 1)
            <p class="resumen__frase">Alcanza para un pasaje de camión de $12.</p>
        @else
            <p class="resumen__frase">Aún no llega ni a un pasaje de camión. Todo suma.</p>
        @endif

        <dl class="resumen__datos">
            <div class="dato">
                <dt class="dato__etiqueta">Hoy</dt>
                <dd class="dato__valor">{{ $dinero($totalHoy) }}</dd>
            </div>
            <div class="dato">
                <dt class="dato__etiqueta">Gastos anotados</dt>
                <dd class="dato__valor">{{ number_format((int) $cantidadMes) }}</dd>
            </div>
            <div class="dato">
                <dt class="dato__etiqueta">Promedio diario</dt>
                <dd class="dato__valor">{{ $dinero($promedioDiario) }}</dd>
            </div>
        </dl>
    </section>

    @if ($gastos->isEmpty())
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

            @if ($categoria)
                <h2 class="vacio__titulo" id="vacio-titulo">No hay nada en «{{ $categoria }}» este mes</h2>
                <p class="vacio__texto">Prueba con otro mes o quita el filtro para ver el rastro completo.</p>
                <a class="boton boton--lleno" href="{{ route('gastos.index', ['mes' => $fechaMes->format('Y-m')]) }}">Ver todas las categorías</a>
            @else
                <h2 class="vacio__titulo" id="vacio-titulo">El rastro está en blanco</h2>
                <p class="vacio__texto">Anota el primer gasto del mes. Un café, el camión, la botana de la tarde: todo deja huella.</p>
                <a class="boton boton--lleno" href="{{ route('gastos.create') }}">Anotar el primer gasto</a>
            @endif
        </section>
    @else
        <section class="rastro-seccion" aria-labelledby="rastro-titulo">
            <div class="rastro-seccion__cabeza">
                <h2 class="titulo-seccion" id="rastro-titulo">El rastro del mes</h2>
                <p class="rastro-seccion__nota">
                    Cada bloque es un gasto {{ $mesCompleto ? 'del mes' : 'de esta página' }}; su ancho, lo que costó.
                    Pasa el cursor o llega con Tab para ver cuál es.
                </p>
            </div>

            <div class="rastro">
                @foreach ($rastro as $indice => $gasto)
                    <a
                        class="rastro__pieza"
                        href="{{ route('gastos.show', $gasto) }}"
                        style="--peso: {{ max((float) $gasto->monto, 0.01) }}; --i: {{ $indice }};"
                        aria-label="{{ $gasto->descripcion }}: {{ $dinero($gasto->monto) }}, {{ $gasto->categoria }}"
                    >
                        <span class="rastro__globo" aria-hidden="true">
                            <span class="rastro__globo-monto">{{ $dinero($gasto->monto) }}</span>
                            <span class="rastro__globo-desc">{{ $gasto->descripcion }}</span>
                            <span class="rastro__globo-cat">{{ $gasto->categoria }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <p class="rastro__eje" aria-hidden="true">
                <span class="rastro__eje-fecha">1 {{ $mesesCortos[(int) $fechaMes->month] }}</span>
                <span class="rastro__eje-linea"></span>
                <span class="rastro__eje-fecha">{{ $diasMes }} {{ $mesesCortos[(int) $fechaMes->month] }}</span>
            </p>
        </section>

        @if ($gastosPorCategoria->isNotEmpty())
            <section class="categorias" aria-labelledby="categorias-titulo">
                <h2 class="titulo-seccion" id="categorias-titulo">En qué se fue</h2>

                <ul class="categorias__lista">
                    @foreach ($gastosPorCategoria as $nombreCategoria => $montoCategoria)
                        @php
                            $anchoBarra = $maxCategoria > 0 ? max(2, round(((float) $montoCategoria / $maxCategoria) * 100, 1)) : 0;
                            $parteDelTotal = $totalCategorias > 0 ? round(((float) $montoCategoria / $totalCategorias) * 100) : 0;
                        @endphp
                        <li class="categorias__fila">
                            <span class="categorias__punto" style="background: {{ $colorCategoria((string) $nombreCategoria) }}" aria-hidden="true"></span>
                            <span class="categorias__nombre">{{ $nombreCategoria }}</span>
                            <span class="categorias__monto">{{ $dinero($montoCategoria) }} <span class="categorias__parte">{{ $parteDelTotal }}%</span></span>
                            <span class="categorias__barra" aria-hidden="true">
                                <span class="categorias__barra-relleno" style="width: {{ $anchoBarra }}%; background: {{ $colorCategoria((string) $nombreCategoria) }}"></span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="lista-gastos" aria-labelledby="lista-titulo">
            <div class="lista-gastos__cabeza">
                <h2 class="titulo-seccion" id="lista-titulo">Los gastos, uno por uno</h2>
                <p class="lista-gastos__cuenta">
                    {{ number_format((int) $gastos->total()) }} {{ $gastos->total() === 1 ? 'gasto' : 'gastos' }}
                </p>
            </div>

            <table class="tabla-gastos">
                <caption class="oculto">
                    Gastos del mes con fecha, descripción, categoría y monto. La última columna enlaza a ver,
                    editar o eliminar cada gasto.
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Qué</th>
                        <th scope="col">Categoría</th>
                        <th scope="col" class="celda--monto">Monto</th>
                        <th scope="col"><span class="oculto">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gastos as $gasto)
                        @php
                            $fechaGasto = $gasto->fecha instanceof \DateTimeInterface
                                ? $gasto->fecha
                                : rescue(static fn () => \Illuminate\Support\Carbon::parse((string) $gasto->fecha), null, false);
                        @endphp

                        <tr class="tabla-gastos__fila">
                            <td class="tabla-gastos__celda celda--fecha">
                                @if ($fechaGasto)
                                    <time datetime="{{ $fechaGasto->format('Y-m-d') }}">
                                        {{ $fechaGasto->format('j') }} {{ $mesesCortos[(int) $fechaGasto->format('n')] }}
                                    </time>
                                @else
                                    Sin fecha
                                @endif
                            </td>

                            <td class="tabla-gastos__celda celda--que">
                                <span class="gasto__descripcion">{{ $gasto->descripcion }}</span>
                                @if (filled($gasto->notas))
                                    <span class="gasto__nota">{{ \Illuminate\Support\Str::limit($gasto->notas, 72) }}</span>
                                @endif
                            </td>

                            <td class="tabla-gastos__celda celda--categoria">
                                <span class="gasto__categoria">
                                    <span class="gasto__punto" style="background: {{ $colorCategoria((string) $gasto->categoria) }}" aria-hidden="true"></span>
                                    {{ $gasto->categoria }}
                                </span>
                            </td>

                            <td class="tabla-gastos__celda celda--monto">{{ $dinero($gasto->monto) }}</td>

                            <td class="tabla-gastos__celda celda--acciones">
                                <div class="acciones-fila">
                                    <a class="accion" href="{{ route('gastos.show', $gasto) }}">Ver</a>
                                    <a class="accion" href="{{ route('gastos.edit', $gasto) }}">Editar</a>
                                    <form class="accion-form" method="POST" action="{{ route('gastos.destroy', $gasto) }}" onsubmit="return confirm('¿Eliminar este gasto? No se puede deshacer.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="accion accion--borrar" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($paginador->hasPages())
                <nav class="paginacion" aria-label="Páginas de gastos">
                    @if ($paginador->onFirstPage())
                        <span class="paginacion__enlace paginacion__enlace--inactivo" aria-disabled="true">Anterior</span>
                    @else
                        <a class="paginacion__enlace" rel="prev" href="{{ $paginador->previousPageUrl() }}">
                            <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false">
                                <path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            Anterior
                        </a>
                    @endif

                    <p class="paginacion__cuenta">Página {{ $paginador->currentPage() }} de {{ $paginador->lastPage() }}</p>

                    @if ($paginador->hasMorePages())
                        <a class="paginacion__enlace" rel="next" href="{{ $paginador->nextPageUrl() }}">
                            Siguiente
                            <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false">
                                <path d="m6 3 5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </a>
                    @else
                        <span class="paginacion__enlace paginacion__enlace--inactivo" aria-disabled="true">Siguiente</span>
                    @endif
                </nav>
            @endif
        </section>
    @endif
@endsection
