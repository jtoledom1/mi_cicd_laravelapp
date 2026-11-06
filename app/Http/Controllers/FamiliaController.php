<?php

namespace App\Http\Controllers;

use App\Models\Familia;
use App\Models\GastoHormiga;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FamiliaController extends Controller
{
    /**
     * Portada del hogar: miembros, código de invitación y lo gastado en familia.
     */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $familia = $usuario->familia();

        $miembros = $familia?->miembros()->orderBy('name')->get() ?? collect();
        $cuentas = $familia?->cuentas()->orderBy('nombre')->get() ?? collect();

        $mes = $this->mesActivo($request);

        $gastosDelMes = $familia === null
            ? collect()
            : GastoHormiga::query()->delHogar($familia->id)->delMes($mes)->get();

        $porMiembro = $familia === null
            ? collect()
            : $gastosDelMes->groupBy('user_id')->map(fn ($grupo) => (float) $grupo->sum('monto'))->sortDesc();

        return view('familia.index', [
            'familia' => $familia,
            'miembros' => $miembros,
            'cuentas' => $cuentas,
            'mes' => $mes,
            'esPropietario' => $familia !== null && $familia->propietario_id === $usuario->id,
            'totalMes' => (float) $gastosDelMes->sum('monto'),
            'cantidadMes' => $gastosDelMes->count(),
            'porMiembro' => $porMiembro,
        ]);
    }

    /**
     * Crea el hogar y deja al usuario dentro como propietario.
     */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
        ], [
            'nombre.required' => 'Ponle nombre a tu hogar.',
            'nombre.max' => 'El nombre no puede pasar de 80 caracteres.',
        ]);

        $usuario = $request->user();

        if ($usuario->familia() !== null) {
            return redirect()
                ->route('familia.index')
                ->with('error', 'Ya perteneces a un hogar.');
        }

        $familia = Familia::create([
            'nombre' => trim($datos['nombre']),
            'propietario_id' => $usuario->id,
            'codigo_invitacion' => Familia::generarCodigo(),
        ]);

        $familia->miembros()->attach($usuario->id);
        $familia->cuentaGeneral();

        return redirect()
            ->route('familia.index')
            ->with('status', 'Hogar creado. Comparte el código para que se unan.');
    }

    /**
     * Une al usuario a un hogar con el código de invitación.
     */
    public function unirse(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:12'],
        ], [
            'codigo.required' => 'Escribe el código de invitación.',
        ]);

        $usuario = $request->user();

        if ($usuario->familia() !== null) {
            return redirect()
                ->route('familia.index')
                ->with('error', 'Ya perteneces a un hogar. Sal del actual para unirte a otro.');
        }

        $familia = Familia::query()
            ->where('codigo_invitacion', mb_strtoupper(trim($datos['codigo'])))
            ->first();

        if ($familia === null) {
            throw ValidationException::withMessages([
                'codigo' => 'Ese código no corresponde a ningún hogar.',
            ]);
        }

        $familia->miembros()->attach($usuario->id);

        return redirect()
            ->route('familia.index')
            ->with('status', 'Ya eres parte de '.$familia->nombre.'.');
    }

    /**
     * Saca al usuario del hogar (el propietario no puede salir: lo elimina).
     */
    public function salir(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        $familia = $usuario->familia();

        if ($familia === null) {
            return redirect()->route('familia.index');
        }

        if ($familia->propietario_id === $usuario->id) {
            return redirect()
                ->route('familia.index')
                ->with('error', 'Eres quien creó el hogar. Para salir, elimínalo.');
        }

        $familia->miembros()->detach($usuario->id);

        return redirect()
            ->route('familia.index')
            ->with('status', 'Saliste de '.$familia->nombre.'.');
    }

    /**
     * Elimina el hogar completo. Solo el propietario.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        $familia = $usuario->familia();

        if ($familia === null) {
            return redirect()->route('familia.index');
        }

        if ($familia->propietario_id !== $usuario->id) {
            abort(403, 'Solo quien creó el hogar puede eliminarlo.');
        }

        $nombre = $familia->nombre;
        $familia->delete();

        return redirect()
            ->route('familia.index')
            ->with('status', 'El hogar '.$nombre.' se eliminó junto con sus gastos compartidos.');
    }

    /**
     * Genera un código de invitación nuevo. Solo el propietario.
     */
    public function regenerarCodigo(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        $familia = $usuario->familia();

        if ($familia === null) {
            return redirect()->route('familia.index');
        }

        if ($familia->propietario_id !== $usuario->id) {
            abort(403, 'Solo quien creó el hogar puede cambiar el código.');
        }

        $familia->update(['codigo_invitacion' => Familia::generarCodigo()]);

        return redirect()
            ->route('familia.index')
            ->with('status', 'Código de invitación renovado.');
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
