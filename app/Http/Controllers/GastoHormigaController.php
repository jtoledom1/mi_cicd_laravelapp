<?php

namespace App\Http\Controllers;

use App\Http\Requests\GastoRequest;
use App\Models\GastoHormiga;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GastoHormigaController extends Controller
{
    /**
     * Muestra los gastos del mes activo junto con los totales del resumen.
     */
    public function index(Request $request): View
    {
        $mes = $this->mesActivo($request);
        $categoria = $this->categoriaActiva($request);

        $gastos = GastoHormiga::query()
            ->delMes($mes)
            ->when(
                $categoria !== null,
                fn (Builder $consulta): Builder => $consulta->where('categoria', $categoria)
            )
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $totalMes = (float) GastoHormiga::query()->delMes($mes)->sum('monto');
        $cantidadMes = GastoHormiga::query()->delMes($mes)->count();

        $totalHoy = (float) GastoHormiga::query()
            ->whereDate('fecha', today()->toDateString())
            ->sum('monto');

        $promedioDiario = $totalMes / max(1, $this->diasBaseParaPromedio($mes));

        $porCategoria = GastoHormiga::query()
            ->delMes($mes)
            ->selectRaw('categoria, SUM(monto) as total')
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->pluck('total', 'categoria')
            ->map(static fn (mixed $total): float => (float) $total);

        return view('gastos.index', [
            'gastos' => $gastos,
            'mes' => $mes,
            'categoria' => $categoria,
            'totalMes' => $totalMes,
            'totalHoy' => $totalHoy,
            'cantidadMes' => $cantidadMes,
            'promedioDiario' => $promedioDiario,
            'porCategoria' => $porCategoria,
            'categorias' => GastoHormiga::CATEGORIAS,
        ]);
    }

    /**
     * Muestra el formulario para registrar un gasto nuevo.
     */
    public function create(): View
    {
        $gasto = new GastoHormiga([
            'fecha' => today(),
            'categoria' => 'Café',
        ]);

        return view('gastos.create', [
            'gasto' => $gasto,
            'categorias' => GastoHormiga::CATEGORIAS,
        ]);
    }

    /**
     * Guarda un gasto hormiga recién registrado.
     */
    public function store(GastoRequest $request): RedirectResponse
    {
        GastoHormiga::create($request->validated());

        return redirect()
            ->route('gastos.index')
            ->with('status', 'Gasto registrado.');
    }

    /**
     * Muestra el detalle de un gasto hormiga.
     */
    public function show(GastoHormiga $gasto): View
    {
        return view('gastos.show', compact('gasto'));
    }

    /**
     * Muestra el formulario para editar un gasto existente.
     */
    public function edit(GastoHormiga $gasto): View
    {
        return view('gastos.edit', [
            'gasto' => $gasto,
            'categorias' => GastoHormiga::CATEGORIAS,
        ]);
    }

    /**
     * Actualiza los datos de un gasto hormiga.
     */
    public function update(GastoRequest $request, GastoHormiga $gasto): RedirectResponse
    {
        $gasto->update($request->validated());

        return redirect()
            ->route('gastos.index')
            ->with('status', 'Gasto actualizado.');
    }

    /**
     * Elimina un gasto hormiga.
     */
    public function destroy(GastoHormiga $gasto): RedirectResponse
    {
        $gasto->delete();

        return redirect()
            ->route('gastos.index')
            ->with('status', 'Gasto eliminado.');
    }

    /**
     * Devuelve el mes activo (formato "YYYY-MM") o el mes actual.
     */
    private function mesActivo(Request $request): string
    {
        $mes = $request->query('mes');

        if (is_string($mes) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) === 1) {
            return $mes;
        }

        return now()->format('Y-m');
    }

    /**
     * Devuelve la categoría activa o null cuando no es una categoría válida.
     */
    private function categoriaActiva(Request $request): ?string
    {
        $categoria = $request->query('categoria');

        if (is_string($categoria) && in_array($categoria, GastoHormiga::CATEGORIAS, true)) {
            return $categoria;
        }

        return null;
    }

    /**
     * Días que se usan para promediar el gasto diario del mes activo.
     */
    private function diasBaseParaPromedio(string $mes): int
    {
        if ($mes === now()->format('Y-m')) {
            return now()->day;
        }

        return CarbonImmutable::createFromFormat('!Y-m', $mes)->daysInMonth;
    }
}
