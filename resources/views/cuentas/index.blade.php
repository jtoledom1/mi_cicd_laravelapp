@extends('layouts.app')

@section('title', 'Cuentas | Gastos Hormiga')

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

        $tieneHogar = $ambito->esFamilia() || auth()->user()?->familia() !== null;
        $otrasCuentas = $resumen->reject(
            static fn ($item) => mb_strtolower((string) $item->cuenta->nombre) === 'general'
        );
        $sinCuentasPropias = $otrasCuentas->isEmpty();
        $maxMes = (float) ($resumen->max(static fn ($item) => (float) $item->totalMes) ?? 0);
    @endphp

    <header class="cue-cabeza">
        <h1 class="cue-titulo">Cuentas</h1>
        <p class="cue-bajada">
            Cada cuenta es de dónde salió el dinero: el de la abuelita, el presupuesto del gas, la general.
            Anotar cada gasto en la suya te dice cuánto ha salido de cada bolsa.
        </p>
    </header>

    <nav class="cue-ambitos" aria-label="Espacio de las cuentas">
        <a
            class="cue-ambito{{ $ambito->esFamilia() ? '' : ' cue-ambito--activo' }}"
            href="{{ route('cuentas.index') }}"
            @if (! $ambito->esFamilia()) aria-current="page" @endif
        >Personales</a>

        @if ($tieneHogar)
            <a
                class="cue-ambito{{ $ambito->esFamilia() ? ' cue-ambito--activo' : '' }}"
                href="{{ route('cuentas.index', ['ambito' => 'familia']) }}"
                @if ($ambito->esFamilia()) aria-current="page" @endif
            >Del hogar</a>
        @else
            <span class="cue-ambito cue-ambito--inactivo" aria-disabled="true">Del hogar</span>
        @endif
    </nav>

    <p class="cue-contexto">
        @if ($ambito->esFamilia())
            Viendo las cuentas de <strong>{{ $ambito->nombre() }}</strong>.
        @else
            Viendo tus cuentas personales.
            @if (! $tieneHogar)
                <a href="{{ route('familia.index') }}">Crea un hogar o únete a uno</a> para tener también cuentas compartidas.
            @endif
        @endif
    </p>

    @if ($errors->any())
        <div class="errores cue-errores" role="alert">
            <p class="errores__titulo">
                @if ($errors->count() === 1)
                    No se pudo guardar el cambio.
                @else
                    Revisa estos {{ $errors->count() }} avisos.
                @endif
            </p>
            <ul class="errores__lista">
                @foreach ($errors->all() as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <dl class="cue-totales">
        <div class="dato">
            <dt class="dato__etiqueta">Gastado en {{ $nombreMes }}</dt>
            <dd class="dato__valor">{{ $dinero($totalMes) }}</dd>
        </div>
        <div class="dato">
            <dt class="dato__etiqueta">Histórico en cuentas</dt>
            <dd class="dato__valor">{{ $dinero($total) }}</dd>
        </div>
        <div class="dato">
            <dt class="dato__etiqueta">Cuentas abiertas</dt>
            <dd class="dato__valor">{{ number_format($cuentas->count()) }}</dd>
        </div>
    </dl>

    <div class="cue-rejilla">
        <section class="cue-lista" aria-labelledby="cue-lista-titulo">
            <div class="cue-lista__cabeza">
                <h2 class="titulo-seccion" id="cue-lista-titulo">Cuenta por cuenta</h2>
                <p class="cue-lista__nota">
                    La cuenta General recibe lo que no tiene cuenta propia y no se puede borrar.
                    Cada cuenta muestra lo del mes, lo acumulado y cuántos gastos lleva.
                </p>
            </div>

            <table class="cue-tabla">
                <caption class="oculto">
                    Cuentas de este espacio: lo gastado en el mes, el total histórico, el número de gastos
                    y las acciones para renombrar o eliminar cada cuenta.
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Cuenta</th>
                        <th scope="col" class="cue-celda--num">Este mes</th>
                        <th scope="col" class="cue-celda--num">Histórico</th>
                        <th scope="col" class="cue-celda--num">Gastos</th>
                        <th scope="col"><span class="oculto">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resumen as $item)
                        @php
                            $cuenta = $item->cuenta;
                            $esGeneral = mb_strtolower((string) $cuenta->nombre) === 'general';
                            $parteMes = $maxMes > 0 ? ((float) $item->totalMes / $maxMes) * 100 : 0;
                        @endphp

                        <tr class="cue-fila">
                            <td class="cue-celda cue-celda--cuenta">
                                <span class="cue-cuenta__linea">
                                    <span class="cue-cuenta__nombre">{{ $cuenta->nombre }}</span>
                                    @if ($esGeneral)
                                        <span class="cue-cuenta__etiqueta">Predeterminada</span>
                                    @endif
                                </span>

                                @if ((float) $item->totalMes > 0)
                                    <span class="cue-cuenta__barra" aria-hidden="true">
                                        <span class="cue-cuenta__relleno" style="width: {{ round($parteMes, 1) }}%"></span>
                                    </span>
                                @endif
                            </td>

                            <td class="cue-celda cue-celda--num" data-label="Este mes">{{ $dinero($item->totalMes) }}</td>
                            <td class="cue-celda cue-celda--num" data-label="Histórico">{{ $dinero($item->total) }}</td>
                            <td class="cue-celda cue-celda--num" data-label="Gastos">{{ number_format((int) $item->cantidad) }}</td>

                            <td class="cue-celda cue-celda--acciones">
                                <details class="cue-editar">
                                    <summary class="cue-editar__disparador">
                                        Renombrar<span class="oculto"> la cuenta {{ $cuenta->nombre }}</span>
                                    </summary>

                                    <div class="cue-editar__panel">
                                        <form class="cue-editar__form" method="POST" action="{{ route('cuentas.update', $cuenta) }}">
                                            @csrf
                                            @method('PATCH')

                                            <label class="oculto" for="cue-nombre-{{ $cuenta->id }}">Nuevo nombre para {{ $cuenta->nombre }}</label>
                                            <input
                                                class="campo__control cue-editar__control"
                                                id="cue-nombre-{{ $cuenta->id }}"
                                                name="nombre"
                                                type="text"
                                                value="{{ $cuenta->nombre }}"
                                                maxlength="60"
                                                required
                                            >

                                            <button class="boton boton--fantasma cue-editar__guardar" type="submit">Guardar</button>
                                        </form>

                                        @if ($esGeneral)
                                            <p class="cue-editar__nota">La cuenta General no se puede eliminar.</p>
                                        @else
                                            <form
                                                class="cue-editar__borrar"
                                                method="POST"
                                                action="{{ route('cuentas.destroy', $cuenta) }}"
                                                onsubmit="return confirm('¿Eliminar esta cuenta? Sus gastos pasan a la cuenta General. No se puede deshacer.');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="accion accion--borrar" type="submit">Eliminar cuenta</button>
                                            </form>
                                        @endif
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($sinCuentasPropias)
                <section class="vacio cue-vacio" aria-labelledby="cue-vacio-titulo">
                    <h2 class="vacio__titulo" id="cue-vacio-titulo">Solo está la General</h2>
                    <p class="vacio__texto">
                        Todo lo que anotas cae en la misma bolsa. Abre una cuenta por cada dinero que quieras
                        seguir de cerca: el gas, la despensa, lo de tu abuelita. Cada gasto se carga a una y
                        el total del mes se arma solo.
                    </p>

                    <ul class="cue-vacio__ejemplos">
                        <li>Gas</li>
                        <li>Despensa</li>
                        <li>Dinero de mi abuelita</li>
                    </ul>
                </section>
            @endif
        </section>

        <aside class="cue-lado">
            <section class="cue-alta" aria-labelledby="cue-alta-titulo">
                <h2 class="cue-alta__titulo" id="cue-alta-titulo">Nueva cuenta</h2>
                <p class="cue-alta__texto">
                    Ponle el nombre con el que la nombras en casa. Se crea en
                    {{ $ambito->esFamilia() ? 'el hogar' : 'tu espacio personal' }}.
                </p>

                <form class="cue-form" method="POST" action="{{ route('cuentas.store') }}">
                    @csrf
                    <input type="hidden" name="ambito" value="{{ $ambito->tipo }}">

                    <div class="campo">
                        <label class="campo__etiqueta" for="cue-nueva">Nombre</label>
                        <input
                            class="campo__control"
                            id="cue-nueva"
                            name="nombre"
                            type="text"
                            value="{{ old('nombre') }}"
                            maxlength="60"
                            placeholder="Dinero de mi abuelita"
                            autocomplete="off"
                            required
                        >
                    </div>

                    <button class="boton boton--lleno" type="submit">Crear cuenta</button>
                </form>

                <p class="cue-alta__nota">Lo que se carga a una cuenta se puede mover a otra al editarlo.</p>
            </section>
        </aside>
    </div>
@endsection
