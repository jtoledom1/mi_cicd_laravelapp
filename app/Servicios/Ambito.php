<?php

namespace App\Servicios;

use App\Models\Cuenta;
use App\Models\Familia;
use App\Models\GastoHormiga;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * El "espacio" en el que se está trabajando: los gastos personales de quien
 * tiene la sesión, o los gastos compartidos de su familia.
 */
final class Ambito
{
    public const PERSONAL = 'personal';

    public const FAMILIA = 'familia';

    private function __construct(
        public readonly string $tipo,
        public readonly ?Familia $familia,
    ) {}

    /**
     * Resuelve el ámbito pedido en la petición. Si se pide la familia y el
     * usuario no tiene una, cae a personal en vez de fallar.
     */
    public static function desde(Request $request, ?User $usuario = null): self
    {
        $usuario ??= $request->user();

        $pedido = $request->query('ambito', $request->input('ambito'));
        $pedido = is_string($pedido) ? strtolower(trim($pedido)) : self::PERSONAL;

        if ($pedido === self::FAMILIA && $usuario !== null) {
            $familia = $usuario->familia();

            if ($familia !== null) {
                return new self(self::FAMILIA, $familia);
            }
        }

        return new self(self::PERSONAL, null);
    }

    /**
     * Ámbito al que pertenece un gasto concreto.
     */
    public static function desdeGasto(GastoHormiga $gasto): self
    {
        if ($gasto->familia_id === null) {
            return new self(self::PERSONAL, null);
        }

        return new self(self::FAMILIA, $gasto->familia);
    }

    public function esFamilia(): bool
    {
        return $this->tipo === self::FAMILIA;
    }

    public function familiaId(): ?int
    {
        return $this->familia?->id;
    }

    /**
     * Nombre para mostrar en la interfaz.
     */
    public function nombre(): string
    {
        return $this->esFamilia() ? (string) $this->familia?->nombre : 'Personal';
    }

    /**
     * Consulta de gastos del ámbito.
     *
     * @return Builder<GastoHormiga>
     */
    public function gastos(User $usuario): Builder
    {
        if ($this->esFamilia()) {
            return GastoHormiga::query()->delHogar((int) $this->familia?->id);
        }

        return GastoHormiga::query()
            ->where('user_id', $usuario->id)
            ->whereNull('familia_id');
    }

    /**
     * Cuentas disponibles en el ámbito.
     *
     * @return Collection<int, Cuenta>
     */
    public function cuentas(User $usuario): Collection
    {
        $consulta = $this->esFamilia()
            ? Cuenta::query()->delHogar((int) $this->familia?->id)
            : Cuenta::query()->personales($usuario->id);

        return $consulta->orderBy('nombre')->get();
    }

    /**
     * Cuenta "General" del ámbito, creándola si no existe.
     */
    public function cuentaGeneral(User $usuario): Cuenta
    {
        return $this->esFamilia()
            ? $this->familia->cuentaGeneral()
            : $usuario->cuentaGeneral();
    }

    /**
     * Devuelve la cuenta pedida si el usuario puede usarla (una personal suya
     * o una de su hogar), o la cuenta general del ámbito.
     *
     * La cuenta manda: si es del hogar, el gasto queda como familiar aunque
     * la petición no lo declare.
     *
     * @throws ValidationException
     */
    public function resolverCuenta(User $usuario, mixed $cuentaId): Cuenta
    {
        if (is_string($cuentaId) && trim($cuentaId) !== '' || is_int($cuentaId)) {
            $familiaId = $usuario->familia()?->id;

            $cuenta = Cuenta::query()
                ->where(function (Builder $consulta) use ($usuario, $familiaId): void {
                    $consulta->where(function (Builder $personal) use ($usuario): void {
                        $personal->where('user_id', $usuario->id)->whereNull('familia_id');
                    });

                    if ($familiaId !== null) {
                        $consulta->orWhere('familia_id', $familiaId);
                    }
                })
                ->find((int) $cuentaId);

            if ($cuenta === null) {
                throw ValidationException::withMessages([
                    'cuenta_id' => 'Esa cuenta no existe o no es tuya.',
                ]);
            }

            return $cuenta;
        }

        $cuentas = $this->cuentas($usuario);
        $general = $cuentas->firstWhere('nombre', 'General');

        return $general ?? $cuentas->first() ?? $this->cuentaGeneral($usuario);
    }

    /**
     * Parámetros para conservar el ámbito en enlaces y redirecciones.
     *
     * @return array<string, string>
     */
    public function parametros(): array
    {
        return $this->esFamilia() ? ['ambito' => self::FAMILIA] : [];
    }
}
