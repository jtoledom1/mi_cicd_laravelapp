<?php

namespace App\Http\Controllers;

use App\Models\Cuenta;
use App\Models\GastoHormiga;
use App\Servicios\Ambito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CuentaController extends Controller
{
    /**
     * Lista las cuentas del ámbito con lo gastado en cada una.
     */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $ambito = Ambito::desde($request, $usuario);
        $mes = $this->mesActivo($request);

        $resumen = $ambito->cuentas($usuario)->map(fn (Cuenta $cuenta): object => (object) [
            'cuenta' => $cuenta,
            'totalMes' => (float) GastoHormiga::query()->where('cuenta_id', $cuenta->id)->delMes($mes)->sum('monto'),
            'total' => (float) GastoHormiga::query()->where('cuenta_id', $cuenta->id)->sum('monto'),
            'cantidad' => GastoHormiga::query()->where('cuenta_id', $cuenta->id)->count(),
        ]);

        return view('cuentas.index', [
            'ambito' => $ambito,
            'mes' => $mes,
            'resumen' => $resumen,
            'cuentas' => $ambito->cuentas($usuario),
            'totalMes' => (float) $resumen->sum('totalMes'),
            'total' => (float) $resumen->sum('total'),
        ]);
    }

    /**
     * Crea una cuenta nueva en el ámbito activo.
     */
    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        $ambito = Ambito::desde($request, $usuario);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
            'ambito' => ['nullable', 'in:personal,familia'],
        ], [
            'nombre.required' => 'Ponle nombre a la cuenta.',
            'nombre.max' => 'El nombre no puede pasar de 60 caracteres.',
        ]);

        $nombre = trim($datos['nombre']);
        $this->validarNombreLibre($nombre, $ambito->cuentas($usuario));

        Cuenta::create([
            'nombre' => $nombre,
            'user_id' => $ambito->esFamilia() ? null : $usuario->id,
            'familia_id' => $ambito->familiaId(),
        ]);

        return redirect()
            ->route('cuentas.index', $ambito->parametros())
            ->with('status', 'Cuenta creada.');
    }

    /**
     * Renombra una cuenta.
     */
    public function update(Request $request, Cuenta $cuenta): RedirectResponse
    {
        $this->autorizar($cuenta);
        $ambito = Ambito::desde($request, $request->user());

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60'],
        ], [
            'nombre.required' => 'Ponle nombre a la cuenta.',
            'nombre.max' => 'El nombre no puede pasar de 60 caracteres.',
        ]);

        $nombre = trim($datos['nombre']);
        $otras = $ambito->cuentas($request->user())->reject(fn (Cuenta $c): bool => $c->id === $cuenta->id);
        $this->validarNombreLibre($nombre, $otras);

        $cuenta->update(['nombre' => $nombre]);

        return redirect()
            ->route('cuentas.index', $ambito->parametros())
            ->with('status', 'Cuenta actualizada.');
    }

    /**
     * Elimina una cuenta y mueve sus gastos a la cuenta general.
     */
    public function destroy(Request $request, Cuenta $cuenta): RedirectResponse
    {
        $this->autorizar($cuenta);
        $ambito = Ambito::desde($request, $request->user());

        $general = $ambito->cuentaGeneral($request->user());

        if ($general->id === $cuenta->id) {
            throw ValidationException::withMessages([
                'cuenta' => 'La cuenta general no se puede eliminar.',
            ]);
        }

        $cuenta->gastos()->update(['cuenta_id' => $general->id]);
        $cuenta->delete();

        return redirect()
            ->route('cuentas.index', $ambito->parametros())
            ->with('status', 'Cuenta eliminada. Sus gastos pasaron a General.');
    }

    /**
     * @param  Collection<int, Cuenta>  $cuentas
     *
     * @throws ValidationException
     */
    private function validarNombreLibre(string $nombre, Collection $cuentas): void
    {
        $repetida = $cuentas->contains(
            fn (Cuenta $cuenta): bool => mb_strtolower($cuenta->nombre) === mb_strtolower($nombre)
        );

        if ($repetida) {
            throw ValidationException::withMessages([
                'nombre' => 'Ya existe una cuenta con ese nombre.',
            ]);
        }
    }

    /**
     * Solo se tocan las cuentas propias o las del hogar al que perteneces.
     */
    private function autorizar(Cuenta $cuenta): void
    {
        $usuario = auth()->user();

        if ($cuenta->familia_id !== null) {
            abort_unless($usuario->familia()?->id === $cuenta->familia_id, 404);

            return;
        }

        abort_unless($cuenta->user_id === $usuario->id, 404);
    }

    /**
     * Mes activo (formato "YYYY-MM") o el actual.
     */
    private function mesActivo(Request $request): string
    {
        $mes = $request->query('mes');

        if (is_string($mes) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) === 1) {
            return $mes;
        }

        return now()->format('Y-m');
    }
}
