<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <title>{{ $archivo ?? 'gastos-hormiga' }}</title>
    <style>
        /* Documento suelto para dompdf: sin grid, sin flex, sin fuentes externas. */
        @page {
            size: letter portrait;
            margin: 18mm;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: #FFFFFF;
            color: #0E3B36;
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 8.6pt;
            line-height: 1.38;
        }

        p {
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* --- Membrete ------------------------------------------------------ */
        .membrete {
            padding-top: 7pt;
            border-top: 3pt solid #0E3B36;
        }

        .membrete__tabla td {
            padding: 0;
            vertical-align: top;
            border: 0;
        }

        .membrete__marca {
            width: 58%;
        }

        .membrete__lado {
            width: 42%;
            text-align: right;
        }

        .membrete__titulo {
            font-size: 17pt;
            font-weight: bold;
            letter-spacing: -0.4pt;
            line-height: 1.05;
            color: #0E3B36;
        }

        .membrete__ambito {
            margin-top: 3pt;
            font-size: 9.4pt;
            font-weight: bold;
            color: #B5134F;
        }

        .membrete__periodo {
            font-size: 10.4pt;
            font-weight: bold;
            line-height: 1.2;
        }

        .membrete__dato {
            margin-top: 2pt;
            font-size: 8.2pt;
            color: #4A5F58;
        }

        /* --- Totales ------------------------------------------------------- */
        .totales {
            margin-top: 9pt;
            border-top: 1pt solid #A6B7A8;
            border-bottom: 1pt solid #A6B7A8;
        }

        .totales td {
            padding: 7pt 0 8pt;
            vertical-align: top;
            border: 0;
        }

        .totales__principal {
            width: 40%;
        }

        .totales__secundario {
            width: 27%;
            padding-left: 10pt;
        }

        .totales__etiqueta {
            font-size: 6.8pt;
            font-weight: bold;
            letter-spacing: 0.6pt;
            color: #4A5F58;
        }

        .totales__cifra {
            margin-top: 1pt;
            font-size: 20pt;
            font-weight: bold;
            line-height: 1.05;
            color: #0E3B36;
        }

        .totales__valor {
            margin-top: 1pt;
            font-size: 13pt;
            font-weight: bold;
            line-height: 1.15;
        }

        .totales__nota {
            margin-top: 2pt;
            font-size: 7.6pt;
            line-height: 1.35;
            color: #4A5F58;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* --- Tabla de gastos ----------------------------------------------- */
        .gastos {
            margin-top: 12pt;
            table-layout: fixed;
        }

        .gastos thead {
            display: table-header-group;
        }

        .gastos th {
            padding: 5pt 4pt;
            background-color: #0E3B36;
            color: #F2F4EC;
            font-size: 7pt;
            font-weight: bold;
            line-height: 1.2;
            text-align: left;
            letter-spacing: 0.3pt;
            border: 0;
        }

        .gastos td {
            padding: 4.4pt 4pt;
            border-bottom: 0.5pt solid #CBD6C8;
            font-size: 7.8pt;
            line-height: 1.35;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .gastos tr {
            page-break-inside: avoid;
        }

        .gastos tr.par td {
            background-color: #F2F4EC;
        }

        .gastos .num {
            text-align: right;
        }

        .gastos th.num {
            text-align: right;
        }

        .celda-fecha {
            color: #4A5F58;
            white-space: nowrap;
        }

        .celda-desc {
            font-weight: bold;
        }

        .celda-cat,
        .celda-cuenta,
        .celda-quien {
            color: #4A5F58;
        }

        .celda-monto {
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
        }

        .celda-notas {
            max-width: 42mm;
            font-size: 7.2pt;
            color: #4A5F58;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .gastos__vacio td {
            padding: 12pt 4pt;
            color: #4A5F58;
            font-size: 8.4pt;
            text-align: center;
        }

        /* --- Repartos ------------------------------------------------------ */
        .repartos {
            margin-top: 14pt;
            page-break-inside: avoid;
        }

        .repartos > tbody > tr > td {
            vertical-align: top;
            border: 0;
            padding: 0;
        }

        .repartos__celda {
            width: 48%;
        }

        .repartos__hueco {
            width: 4%;
        }

        .repartos__titulo {
            margin-bottom: 4pt;
            padding-bottom: 3pt;
            border-bottom: 1.5pt solid #0E3B36;
            font-size: 8.6pt;
            font-weight: bold;
        }

        .mini {
            width: 100%;
            table-layout: fixed;
        }

        .mini th {
            padding: 3pt 3pt;
            border-bottom: 0.6pt solid #A6B7A8;
            color: #4A5F58;
            font-size: 6.8pt;
            font-weight: bold;
            text-align: left;
            letter-spacing: 0.3pt;
        }

        .mini td {
            padding: 3pt 3pt;
            border-bottom: 0.5pt solid #DFE8DC;
            font-size: 7.6pt;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .mini .num {
            text-align: right;
            white-space: nowrap;
        }

        .mini__vacio {
            color: #4A5F58;
        }

        /* --- Cierre y pie -------------------------------------------------- */
        .cierre {
            margin-top: 12pt;
            padding-top: 6pt;
            border-top: 2pt solid #0E3B36;
            page-break-inside: avoid;
        }

        .cierre td {
            padding: 0;
            border: 0;
            vertical-align: bottom;
        }

        .cierre__rotulo {
            font-size: 8.4pt;
            font-weight: bold;
            color: #4A5F58;
        }

        .cierre__cifra {
            width: 30%;
            font-size: 13pt;
            font-weight: bold;
            text-align: right;
        }

        .pie {
            margin-top: 16pt;
            padding-top: 5pt;
            border-top: 0.5pt solid #CBD6C8;
            color: #4A5F58;
            font-size: 7.2pt;
            line-height: 1.4;
        }

        .pie__marca {
            font-weight: bold;
            color: #0E3B36;
        }
    </style>
</head>
<body>
    @php
        $dinero = static fn ($n) => '$' . number_format((float) $n, 2);

        $mesesLargos = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $generado = $generadoEn instanceof \DateTimeInterface ? $generadoEn : now();
        $generadoTexto = 'Generado el ' . $generado->format('j') . ' de '
            . $mesesLargos[(int) $generado->format('n')] . ' de ' . $generado->format('Y')
            . ' a las ' . $generado->format('H:i');

        $fechaTexto = static function ($gasto): string {
            $fecha = $gasto->fecha instanceof \DateTimeInterface
                ? $gasto->fecha
                : rescue(static fn () => \Illuminate\Support\Carbon::parse((string) $gasto->fecha), null, false);

            return $fecha ? $fecha->format('d/m/Y') : '—';
        };

        // Anchos en porcentaje: suman 100 para que dompdf no desborde la hoja.
        $columnas = $esFamilia
            ? ['fecha' => 8, 'descripcion' => 21, 'categoria' => 11, 'cuenta' => 13, 'quien' => 11, 'monto' => 12, 'notas' => 24]
            : ['fecha' => 8, 'descripcion' => 24, 'categoria' => 13, 'cuenta' => 15, 'monto' => 13, 'notas' => 27];

        $totalColumnas = count($columnas);
    @endphp

    <div class="membrete">
        <table class="membrete__tabla">
            <tr>
                <td class="membrete__marca">
                    <p class="membrete__titulo">Gastos Hormiga</p>
                    <p class="membrete__ambito">{{ $ambitoNombre }}</p>
                </td>
                <td class="membrete__lado">
                    <p class="membrete__periodo">{{ $periodo }}</p>
                    <p class="membrete__dato">Cuenta: {{ $cuentaNombre }}</p>
                    @if ($categoria)
                        <p class="membrete__dato">Categoría: {{ $categoria }}</p>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="totales">
        <tr>
            <td class="totales__principal">
                <p class="totales__etiqueta">TOTAL DEL PERIODO</p>
                <p class="totales__cifra">{{ $dinero($total) }}</p>
            </td>
            <td class="totales__secundario">
                <p class="totales__etiqueta">GASTOS ANOTADOS</p>
                <p class="totales__valor">{{ number_format((int) $cantidad) }}</p>
                <p class="totales__nota">{{ $cantidad === 1 ? 'un solo gasto' : 'gastos chicos, sumados' }}</p>
            </td>
            <td class="totales__secundario">
                <p class="totales__etiqueta">FECHA DE GENERACIÓN</p>
                <p class="totales__nota">{{ $generadoTexto }}</p>
            </td>
        </tr>
    </table>

    <table class="gastos">
        <thead>
            <tr>
                <th style="width: {{ $columnas['fecha'] }}%">Fecha</th>
                <th style="width: {{ $columnas['descripcion'] }}%">Descripción</th>
                <th style="width: {{ $columnas['categoria'] }}%">Categoría</th>
                <th style="width: {{ $columnas['cuenta'] }}%">Cuenta</th>
                @if ($esFamilia)
                    <th style="width: {{ $columnas['quien'] }}%">Quién</th>
                @endif
                <th class="num" style="width: {{ $columnas['monto'] }}%">Monto</th>
                <th style="width: {{ $columnas['notas'] }}%">Notas</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($gastos as $indice => $gasto)
                <tr class="{{ $indice % 2 === 1 ? 'par' : 'impar' }}">
                    <td class="celda-fecha">{{ $fechaTexto($gasto) }}</td>
                    <td class="celda-desc">{{ $gasto->descripcion }}</td>
                    <td class="celda-cat">{{ $gasto->categoria }}</td>
                    <td class="celda-cuenta">{{ $gasto->cuenta?->nombre ?? 'Sin cuenta' }}</td>
                    @if ($esFamilia)
                        <td class="celda-quien">{{ $gasto->user?->name ?? '—' }}</td>
                    @endif
                    <td class="celda-monto">{{ $dinero($gasto->monto) }}</td>
                    <td class="celda-notas">
                        @if (filled($gasto->notas))
                            {{ \Illuminate\Support\Str::limit((string) $gasto->notas, 180, '…') }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="gastos__vacio">
                    <td colspan="{{ $totalColumnas }}">No hay gastos con estos filtros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="repartos">
        <tr>
            <td class="repartos__celda">
                <p class="repartos__titulo">Subtotales por categoría</p>

                <table class="mini">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th class="num">Gastos</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($porCategoria as $fila)
                            <tr>
                                <td>{{ $fila->nombre }}</td>
                                <td class="num">{{ number_format((int) $fila->cantidad) }}</td>
                                <td class="num">{{ $dinero($fila->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="mini__vacio" colspan="3">Sin gastos en el periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>

            <td class="repartos__hueco"></td>

            <td class="repartos__celda">
                <p class="repartos__titulo">Subtotales por cuenta</p>

                <table class="mini">
                    <thead>
                        <tr>
                            <th>Cuenta</th>
                            <th class="num">Gastos</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($porCuenta as $fila)
                            <tr>
                                <td>{{ $fila->nombre }}</td>
                                <td class="num">{{ number_format((int) $fila->cantidad) }}</td>
                                <td class="num">{{ $dinero($fila->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="mini__vacio" colspan="3">Sin gastos en el periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <table class="cierre">
        <tr>
            <td class="cierre__rotulo">
                Total general · {{ number_format((int) $cantidad) }} {{ $cantidad === 1 ? 'gasto' : 'gastos' }}
            </td>
            <td class="cierre__cifra">{{ $dinero($total) }}</td>
        </tr>
    </table>

    <div class="pie">
        <p>Descargado por {{ $usuario->name }} · <span class="pie__marca">Gastos Hormiga</span></p>
        <p>{{ $generadoTexto }} · {{ $periodo }} · {{ $cuentaNombre }}</p>
    </div>
</body>
</html>
