@extends('layouts.app')

@section('title', e($gasto->descripcion) . ' | Gastos Hormiga')

@section('content')
    @php
        $dinero = static fn ($n) => '$' . number_format((float) $n, 2);

        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $fecha = $gasto->fecha instanceof \DateTimeInterface
            ? $gasto->fecha
            : rescue(static fn () => \Illuminate\Support\Carbon::parse((string) $gasto->fecha), null, false);

        $paleta = ['#0E3B36', '#B5134F', '#B98A1F', '#3F6F63', '#8A5A2B', '#5B4B8A'];
        $colorCategoria = static fn (string $nombre) => $paleta[abs((int) crc32($nombre)) % count($paleta)];

        $enlaceRastro = route(
            'gastos.index',
            array_merge($fecha ? ['mes' => $fecha->format('Y-m')] : [], $ambito->parametros())
        );

        $enlaceCuentas = route('cuentas.index', $ambito->parametros());
        $esFamilia = $ambito->esFamilia();
        $nombreEspacio = $esFamilia ? $ambito->nombre() : 'Personal';
        $nombreCuenta = $gasto->cuenta?->nombre ?? 'Sin cuenta';
        $quienAnoto = $gasto->user?->name;
    @endphp

    <a class="volver" href="{{ route('gastos.index', $ambito->parametros()) }}">
        <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false">
            <path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
        {{ $esFamilia ? 'Gastos del hogar' : 'Mis gastos' }}
    </a>

    <article class="ficha">
        <p class="ficha__meta">
            <span class="ficha__espacio{{ $esFamilia ? ' ficha__espacio--hogar' : '' }}">{{ $nombreEspacio }}</span>

            <a class="gasto__cuenta ficha__cuenta" href="{{ $enlaceCuentas }}">
                <svg viewBox="0 0 16 16" width="13" height="13" aria-hidden="true" focusable="false">
                    <path d="M2.5 5.5h11v7h-11zM2.5 5.5 4 3.5h8l1.5 2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Cuenta: {{ $nombreCuenta }}
            </a>
        </p>

        <p class="ficha__categoria">
            <span class="ficha__punto" style="background: {{ $colorCategoria((string) $gasto->categoria) }}" aria-hidden="true"></span>
            {{ $gasto->categoria }}
        </p>

        <h1 class="ficha__titulo">{{ $gasto->descripcion }}</h1>
        <p class="ficha__monto">{{ $dinero($gasto->monto) }}</p>

        @if ($fecha)
            <p class="ficha__fecha">{{ $fecha->format('j') }} de {{ $meses[(int) $fecha->format('n')] }} de {{ $fecha->format('Y') }}</p>
        @endif

        @if ($esFamilia)
            <p class="ficha__quien">
                Anotado por <strong>{{ $quienAnoto ?? 'alguien del hogar' }}</strong>
                <span class="ficha__quien-nota">· se comparte con {{ $nombreEspacio }}</span>
            </p>
        @endif

        @if (filled($gasto->notas))
            <div class="ficha__nota">
                <h2 class="ficha__nota-titulo">Nota</h2>
                <p>{!! nl2br(e($gasto->notas)) !!}</p>
            </div>
        @endif

        <div class="ficha__acciones">
            <a class="boton boton--lleno" href="{{ route('gastos.edit', $gasto) }}">Editar gasto</a>

            <form method="POST" action="{{ route('gastos.destroy', $gasto) }}" onsubmit="return confirm('¿Eliminar este gasto? No se puede deshacer.');">
                @csrf
                @method('DELETE')
                <button class="boton boton--peligro" type="submit">Eliminar</button>
            </form>

            <a class="ficha__enlace-rastro" href="{{ $enlaceRastro }}">Ver el rastro del mes</a>
        </div>
    </article>
@endsection
