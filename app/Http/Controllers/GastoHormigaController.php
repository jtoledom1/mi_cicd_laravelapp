<?php

namespace App\Http\Controllers;

use App\Http\Requests\GastoRequest;
use App\Models\Cuenta;
use App\Models\GastoHormiga;
use App\Models\User;
use App\Servicios\Ambito;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GastoHormigaController extends Controller
{
    /**
     * Listado del mes con el resumen del ámbito activo (personal o familia).
     */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $ambito = Ambito::desde($request, $usuario);
        $mes = $this->mesActivo($request);
        $categoria = $this->categoriaActiva($request);
        $cuenta = $this->cuentaActiva($request, $ambito, $usuario);

        $gastos = $ambito->gastos($usuario)
            ->delMes($mes)
            ->when(
                $categoria !== null,
                fn (Builder $consulta): Builder => $consulta->where('categoria', $categoria)
            )
            ->when(
                $cuenta !== null,
                fn (Builder $consulta): Builder => $consulta->where('cuenta_id', $cuenta->id)
            )
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $totalMes = (float) $this->conCuenta($ambito->gastos($usuario)->delMes($mes), $cuenta)->sum('monto');
        $cantidadMes = $this->conCuenta($ambito->gastos($usuario)->delMes($mes), $cuenta)->count();

        $totalHoy = (float) $this->conCuenta($ambito->gastos($usuario)->whereDate('fecha', today()->toDateString()), $cuenta)->sum('monto');

        $promedioDiario = $totalMes / max(1, $this->diasBaseParaPromedio($mes));

        $porCategoria = $this->conCuenta($ambito->gastos($usuario)->delMes($mes), $cuenta)
            ->selectRaw('categoria, SUM(monto) as total')
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->pluck('total', 'categoria')
            ->map(static fn (mixed $total): float => (float) $total);

        return view('gastos.index', [
            'gastos' => $gastos,
            'mes' => $mes,
            'categoria' => $categoria,
            'cuenta' => $cuenta,
            'ambito' => $ambito,
            'cuentas' => $ambito->cuentas($usuario),
            'totalMes' => $totalMes,
            'totalHoy' => $totalHoy,
            'cantidadMes' => $cantidadMes,
            'promedioDiario' => $promedioDiario,
            'porCategoria' => $porCategoria,
            'categorias' => GastoHormiga::CATEGORIAS,
        ]);
    }

    /**
     * Formulario de alta dentro del ámbito activo.
     */
    public function create(Request $request): View
    {
        $usuario = $request->user();
        $ambito = Ambito::desde($request, $usuario);
        $cuentas = $ambito->cuentas($usuario);
        $general = $cuentas->firstWhere('nombre', 'General') ?? $cuentas->first();

        $gasto = new GastoHormiga([
            'fecha' => today(),
            'categoria' => 'Café',
            'cuenta_id' => $general?->id,
        ]);

        return view('gastos.create', [
            'gasto' => $gasto,
            'categorias' => GastoHormiga::CATEGORIAS,
            'ambito' => $ambito,
            'cuentas' => $cuentas,
        ]);
    }

    /**
     * Guarda un gasto en la cuenta y el ámbito elegidos.
     */
    public function store(GastoRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $ambito = Ambito::desde($request, $usuario);
        $cuenta = $ambito->resolverCuenta($usuario, $request->input('cuenta_id'));

        $datos = $request->safe()->except(['cuenta_id', 'ambito']);
        $datos['cuenta_id'] = $cuenta->id;
        $datos['familia_id'] = $cuenta->familia_id;

        $usuario->gastos()->create($datos);

        return redirect()
            ->route('gastos.index', $ambito->parametros())
            ->with('status', 'Gasto registrado.');
    }

    /**
     * Detalle de un gasto.
     */
    public function show(GastoHormiga $gasto): View
    {
        $this->autorizar($gasto);

        return view('gastos.show', [
            'gasto' => $gasto,
            'ambito' => Ambito::desdeGasto($gasto),
        ]);
    }

    /**
     * Formulario de edición.
     */
    public function edit(GastoHormiga $gasto): View
    {
        $this->autorizar($gasto);

        $ambito = Ambito::desdeGasto($gasto);

        return view('gastos.edit', [
            'gasto' => $gasto,
            'categorias' => GastoHormiga::CATEGORIAS,
            'ambito' => $ambito,
            'cuentas' => $ambito->cuentas(auth()->user()),
        ]);
    }

    /**
     * Actualiza el gasto.
     */
    public function update(GastoRequest $request, GastoHormiga $gasto): RedirectResponse
    {
        $this->autorizar($gasto);

        $usuario = $request->user();
        $ambito = Ambito::desdeGasto($gasto);
        $cuenta = $ambito->resolverCuenta($usuario, $request->input('cuenta_id'));

        $datos = $request->safe()->except(['cuenta_id', 'ambito']);
        $datos['cuenta_id'] = $cuenta->id;
        $datos['familia_id'] = $cuenta->familia_id;

        $gasto->update($datos);

        return redirect()
            ->route('gastos.index', $ambito->parametros())
            ->with('status', 'Gasto actualizado.');
    }

    /**
     * Elimina el gasto.
     */
    public function destroy(GastoHormiga $gasto): RedirectResponse
    {
        $this->autorizar($gasto);

        $ambito = Ambito::desdeGasto($gasto);
        $gasto->delete();

        return redirect()
            ->route('gastos.index', $ambito->parametros())
            ->with('status', 'Gasto eliminado.');
    }

    /**
     * Un gasto se puede ver si es propio o si es del hogar al que perteneces.
     */
    private function autorizar(GastoHormiga $gasto): void
    {
        $usuario = auth()->user();

        if ($gasto->familia_id !== null) {
            abort_unless($usuario->familia()?->id === $gasto->familia_id, 404);

            return;
        }

        abort_unless($gasto->user_id === $usuario->id, 404);
    }

    /**
     * Aplica el filtro de cuenta cuando hay una seleccionada.
     *
     * @param  Builder<GastoHormiga>  $consulta
     * @return Builder<GastoHormiga>
     */
    private function conCuenta(Builder $consulta, ?Cuenta $cuenta): Builder
    {
        return $cuenta !== null ? $consulta->where('cuenta_id', $cuenta->id) : $consulta;
    }

    /**
     * Mes activo (formato "YYYY-MM") o el mes actual.
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
     * Categoría activa o null cuando no es válida.
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
     * Cuenta activa del ámbito o null.
     */
    private function cuentaActiva(Request $request, Ambito $ambito, User $usuario): ?Cuenta
    {
        $cuenta = $request->query('cuenta');

        if (! is_string($cuenta) && ! is_int($cuenta)) {
            return null;
        }

        return $ambito->cuentas($usuario)->firstWhere('id', (int) $cuenta);
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
