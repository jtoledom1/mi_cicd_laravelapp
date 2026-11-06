<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\GastoHormiga;
use App\Servicios\Ambito;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    /**
     * Nombres de los meses en español.
     *
     * @var array<int, string>
     */
    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    /**
     * Descarga los gastos filtrados en PDF.
     */
    public function pdf(Request $request): Response
    {
        $datos = $this->datos($request);

        $pdf = Pdf::loadView('exportar.gastos', $datos)->setPaper('letter', 'portrait');

        return $pdf->download($datos['archivo'].'.pdf');
    }

    /**
     * Descarga los gastos filtrados en Excel (.xlsx).
     */
    public function excel(Request $request): BinaryFileResponse
    {
        $datos = $this->datos($request);

        $ruta = tempnam(sys_get_temp_dir(), 'gastos_hormiga_').'.xlsx';

        $escritor = new Writer();
        $escritor->openToFile($ruta);

        $estiloCabecera = (new Style())
            ->withFontBold(true)
            ->withFontColor('F2F4EC')
            ->withBackgroundColor('0E3B36');

        $estiloDinero = (new Style())->withFormat('#,##0.00');
        $estiloTotal = (new Style())->withFontBold(true);

        $escritor->addRow(Row::fromValuesWithStyle(
            ['Gastos Hormiga', $datos['ambitoNombre']],
            (new Style())->withFontBold(true)->withFontSize(14)
        ));
        $escritor->addRow(Row::fromValues([$datos['periodo'], $datos['cuentaNombre']]));
        $escritor->addRow(Row::fromValues([]));

        $encabezados = ['Fecha', 'Descripción', 'Categoría', 'Cuenta'];

        if ($datos['esFamilia']) {
            $encabezados[] = 'Quién';
        }

        $encabezados[] = 'Monto';
        $encabezados[] = 'Notas';

        $escritor->addRow(Row::fromValuesWithStyle($encabezados, $estiloCabecera));

        foreach ($datos['gastos'] as $gasto) {
            $valores = [
                $gasto->fecha instanceof \DateTimeInterface ? $gasto->fecha->format('d/m/Y') : (string) $gasto->fecha,
                $gasto->descripcion,
                $gasto->categoria,
                $gasto->cuenta?->nombre ?? 'Sin cuenta',
            ];

            if ($datos['esFamilia']) {
                $valores[] = $gasto->user?->name ?? '—';
            }

            $valores[] = (float) $gasto->monto;
            $valores[] = (string) ($gasto->notas ?? '');

            $indiceMonto = $datos['esFamilia'] ? 5 : 4;

            $celdas = [];

            foreach ($valores as $posicion => $valor) {
                $celdas[] = Cell::fromValue($valor, $posicion === $indiceMonto ? $estiloDinero : new Style());
            }

            $escritor->addRow(new Row($celdas));
        }

        $escritor->addRow(Row::fromValues([]));

        $etiquetaTotal = ['Total', '', '', ''];

        if ($datos['esFamilia']) {
            $etiquetaTotal[] = '';
        }

        $etiquetaTotal[] = (float) $datos['total'];
        $etiquetaTotal[] = '';

        $escritor->addRow(Row::fromValuesWithStyle($etiquetaTotal, $estiloTotal));

        $escritor->close();

        return response()
            ->download($ruta, $datos['archivo'].'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Junta los gastos filtrados y los datos de cabecera que usan ambas
     * exportaciones. Los filtros son los mismos del listado.
     *
     * @return array<string, mixed>
     */
    private function datos(Request $request): array
    {
        $usuario = $request->user();
        $ambito = Ambito::desde($request, $usuario);
        $mes = $this->mesValido($request->query('mes'));
        $categoria = $this->categoriaValida($request->query('categoria'));
        $cuenta = $this->cuentaDe($request->query('cuenta'), $ambito, $request);

        $gastos = $ambito->gastos($usuario)
            ->when(
                $mes !== null,
                fn ($consulta) => $consulta->delMes($mes)
            )
            ->when(
                $categoria !== null,
                fn ($consulta) => $consulta->where('categoria', $categoria)
            )
            ->when(
                $cuenta !== null,
                fn ($consulta) => $consulta->where('cuenta_id', $cuenta->id)
            )
            ->with(['cuenta', 'user'])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $periodo = 'Todo el historial';

        if ($mes !== null) {
            $fecha = CarbonImmutable::createFromFormat('!Y-m', $mes);
            $periodo = ucfirst(self::MESES[(int) $fecha->month]).' de '.$fecha->year;
        }

        $ambitoNombre = $ambito->esFamilia()
            ? 'Familia · '.$ambito->nombre()
            : 'Personal · '.$usuario->name;

        $partes = ['gastos-hormiga', $ambito->tipo, $mes ?? 'historico'];

        if ($cuenta !== null) {
            $partes[] = $cuenta->nombre;
        }

        return [
            'ambito' => $ambito,
            'ambitoNombre' => $ambitoNombre,
            'esFamilia' => $ambito->esFamilia(),
            'mes' => $mes,
            'periodo' => $periodo,
            'cuenta' => $cuenta,
            'cuentaNombre' => $cuenta?->nombre ?? 'Todas las cuentas',
            'categoria' => $categoria,
            'gastos' => $gastos,
            'cantidad' => $gastos->count(),
            'total' => (float) $gastos->sum('monto'),
            'porCategoria' => $this->reparto($gastos, 'categoria'),
            'porCuenta' => $this->repartoPorCuenta($gastos),
            'generadoEn' => now(),
            'usuario' => $usuario,
            'archivo' => implode('-', array_map(
                static fn (string $parte): string => preg_replace('/[^A-Za-z0-9]+/', '-', $parte) ?: 'x',
                $partes
            )),
        ];
    }

    /**
     * @param  Collection<int, GastoHormiga>  $gastos
     * @return Collection<int, object{nombre: string, total: float, cantidad: int}>
     */
    private function reparto(Collection $gastos, string $campo): Collection
    {
        return $gastos
            ->groupBy($campo)
            ->map(fn (Collection $grupo, string $nombre): object => (object) [
                'nombre' => $nombre,
                'total' => (float) $grupo->sum('monto'),
                'cantidad' => $grupo->count(),
            ])
            ->sortByDesc('total')
            ->values();
    }

    /**
     * @param  Collection<int, GastoHormiga>  $gastos
     * @return Collection<int, object{nombre: string, total: float, cantidad: int}>
     */
    private function repartoPorCuenta(Collection $gastos): Collection
    {
        return $gastos
            ->groupBy(fn (GastoHormiga $gasto): string => $gasto->cuenta?->nombre ?? 'Sin cuenta')
            ->map(fn (Collection $grupo, string $nombre): object => (object) [
                'nombre' => $nombre,
                'total' => (float) $grupo->sum('monto'),
                'cantidad' => $grupo->count(),
            ])
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Mes "YYYY-MM" válido o null (todo el historial).
     */
    private function mesValido(mixed $mes): ?string
    {
        if (is_string($mes) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) === 1) {
            return $mes;
        }

        return null;
    }

    /**
     * Categoría válida o null.
     */
    private function categoriaValida(mixed $categoria): ?string
    {
        if (is_string($categoria) && in_array($categoria, GastoHormiga::CATEGORIAS, true)) {
            return $categoria;
        }

        return null;
    }

    /**
     * Cuenta del ámbito o null.
     */
    private function cuentaDe(mixed $cuenta, Ambito $ambito, Request $request): ?Cuenta
    {
        if (! is_string($cuenta) && ! is_int($cuenta)) {
            return null;
        }

        return $ambito->cuentas($request->user())->firstWhere('id', (int) $cuenta);
    }
}
