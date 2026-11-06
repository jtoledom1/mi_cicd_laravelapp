<?php

namespace App\Http\Controllers;

use App\Models\GastoHormiga;
use App\Servicios\Ambito;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ResumenController extends Controller
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
     * Resumen de un mes: totales, comparación con el mes anterior, reparto por
     * categoría y ritmo día por día.
     */
    public function mensual(Request $request): View
    {
        $mes = $this->mesValido($request->query('mes'));
        $inicio = CarbonImmutable::createFromFormat('!Y-m', $mes)->startOfMonth();
        $fin = $inicio->endOfMonth();
        $usuario = $request->user();
        $ambito = Ambito::desde($request, $usuario);

        $gastosDelMes = $ambito->gastos($usuario)
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $total = (float) $gastosDelMes->sum('monto');
        $cantidad = $gastosDelMes->count();
        $diasDelMes = $inicio->daysInMonth;
        $esMesActual = $inicio->isSameMonth(now());

        $diasBase = $esMesActual ? max(1, now()->day) : $diasDelMes;
        $promedioDiario = $total / $diasBase;

        $totalAnterior = (float) $ambito->gastos($usuario)
            ->delMes($inicio->subMonth()->format('Y-m'))
            ->sum('monto');
        $variacion = $totalAnterior > 0 ? (($total - $totalAnterior) / $totalAnterior) * 100 : null;

        $porDia = array_fill(1, $diasDelMes, 0.0);

        foreach ($gastosDelMes as $gasto) {
            $porDia[(int) $gasto->fecha->format('j')] += (float) $gasto->monto;
        }

        $maximoDia = max($porDia);
        $diaMasCaro = $maximoDia > 0 ? (int) array_search($maximoDia, $porDia, true) : null;

        return view('resumen.mensual', [
            'mes' => $mes,
            'anio' => (int) $inicio->year,
            'numeroMes' => (int) $inicio->month,
            'nombreMes' => self::MESES[(int) $inicio->month].' de '.$inicio->year,
            'total' => $total,
            'cantidad' => $cantidad,
            'diasDelMes' => $diasDelMes,
            'esMesActual' => $esMesActual,
            'promedioDiario' => $promedioDiario,
            'totalAnterior' => $totalAnterior,
            'variacion' => $variacion,
            'porCategoria' => $this->repartoPorCategoria($gastosDelMes),
            'porDia' => $porDia,
            'maximoDia' => $maximoDia,
            'diaMasCaro' => $diaMasCaro,
            'topGastos' => $gastosDelMes->sortByDesc(fn (GastoHormiga $g): float => (float) $g->monto)->take(5)->values(),
            'mesAnterior' => $inicio->subMonth()->format('Y-m'),
            'mesSiguiente' => $inicio->addMonth()->format('Y-m'),
            'nombresMeses' => self::MESES,
            'ambito' => $ambito,
        ]);
    }

    /**
     * Resumen del año: los doce meses, el reparto por categoría y la
     * comparación contra el año anterior.
     */
    public function anual(Request $request): View
    {
        $anio = $this->anioValido($request->query('anio'));
        $usuario = $request->user();
        $ambito = Ambito::desde($request, $usuario);

        $gastosDelAnio = $ambito->gastos($usuario)
            ->whereYear('fecha', $anio)
            ->get();

        $porMes = array_fill(1, 12, 0.0);

        foreach ($gastosDelAnio as $gasto) {
            $porMes[(int) $gasto->fecha->format('n')] += (float) $gasto->monto;
        }

        $total = (float) array_sum($porMes);
        $cantidad = $gastosDelAnio->count();
        $mesesConDatos = count(array_filter($porMes, static fn (float $valor): bool => $valor > 0));
        $promedioMensual = $mesesConDatos > 0 ? $total / $mesesConDatos : 0.0;

        $maximoMes = max($porMes);
        $mejorMes = $maximoMes > 0 ? (int) array_search($maximoMes, $porMes, true) : null;

        $totalAnterior = (float) $ambito->gastos($usuario)->whereYear('fecha', $anio - 1)->sum('monto');
        $variacion = $totalAnterior > 0 ? (($total - $totalAnterior) / $totalAnterior) * 100 : null;

        return view('resumen.anual', [
            'anio' => $anio,
            'anioAnterior' => $anio - 1,
            'anioSiguiente' => $anio + 1,
            'anios' => $this->aniosDisponibles($ambito->gastos($usuario)->pluck('fecha'), $anio),
            'porMes' => $porMes,
            'total' => $total,
            'cantidad' => $cantidad,
            'promedioMensual' => $promedioMensual,
            'mesesConDatos' => $mesesConDatos,
            'maximoMes' => $maximoMes,
            'mejorMes' => $mejorMes,
            'totalAnterior' => $totalAnterior,
            'variacion' => $variacion,
            'porCategoria' => $this->repartoPorCategoria($gastosDelAnio),
            'mesActual' => $anio === (int) now()->year ? (int) now()->month : null,
            'nombresMeses' => self::MESES,
            'ambito' => $ambito,
        ]);
    }

    /**
     * Agrupa los gastos por categoría, de mayor a menor.
     *
     * @param  Collection<int, GastoHormiga>  $gastos
     * @return Collection<int, object{categoria: string, total: float, cantidad: int, parte: float}>
     */
    private function repartoPorCategoria(Collection $gastos): Collection
    {
        $totalGeneral = (float) $gastos->sum('monto');

        return $gastos
            ->groupBy('categoria')
            ->map(fn (Collection $grupo, string $categoria): object => (object) [
                'categoria' => $categoria,
                'total' => (float) $grupo->sum('monto'),
                'cantidad' => $grupo->count(),
                'parte' => $totalGeneral > 0 ? ((float) $grupo->sum('monto') / $totalGeneral) * 100 : 0.0,
            ])
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Años con gastos registrados, más el año que se está consultando.
     *
     * @param  Collection<int, mixed>  $fechas
     * @return list<int>
     */
    private function aniosDisponibles(Collection $fechas, int $anioActual): array
    {
        $anios = $fechas
            ->map(fn (mixed $fecha): int => CarbonImmutable::parse((string) $fecha)->year)
            ->push($anioActual)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return array_map('intval', $anios);
    }

    /**
     * Devuelve un mes "YYYY-MM" válido o el mes actual.
     */
    private function mesValido(mixed $mes): string
    {
        if (is_string($mes) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) === 1) {
            return $mes;
        }

        return now()->format('Y-m');
    }

    /**
     * Devuelve un año razonable o el año actual.
     */
    private function anioValido(mixed $anio): int
    {
        if (is_string($anio) || is_int($anio)) {
            $valor = (int) $anio;

            if ($valor >= 2000 && $valor <= 2100) {
                return $valor;
            }
        }

        return (int) now()->year;
    }
}
