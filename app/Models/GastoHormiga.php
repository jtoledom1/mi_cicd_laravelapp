<?php

namespace App\Models;

use Database\Factories\GastoHormigaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GastoHormiga extends Model
{
    /** @use HasFactory<GastoHormigaFactory> */
    use HasFactory;

    /**
     * Categorías disponibles para clasificar un gasto hormiga.
     *
     * @var list<string>
     */
    public const CATEGORIAS = ['Café', 'Antojo', 'Transporte', 'Botana', 'Suscripción', 'Otro'];

    /**
     * Nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'gastos_hormiga';

    /**
     * Atributos que se pueden asignar de forma masiva.
     *
     * @var list<string>
     */
    protected $fillable = ['descripcion', 'monto', 'categoria', 'fecha', 'notas', 'cuenta_id', 'familia_id'];

    /**
     * Atributos que deben convertirse a tipos nativos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    /**
     * Filtra los gastos que pertenecen a un mes con formato "YYYY-MM".
     *
     * @param  Builder<GastoHormiga>  $query
     * @return Builder<GastoHormiga>
     */
    public function scopeDelMes(Builder $query, string $mes): Builder
    {
        $partes = explode('-', trim($mes));

        if (count($partes) !== 2 || ! ctype_digit($partes[0]) || ! ctype_digit($partes[1])) {
            return $query;
        }

        return $query
            ->whereYear('fecha', (int) $partes[0])
            ->whereMonth('fecha', (int) $partes[1]);
    }

    /**
     * Devuelve la categoría del gasto o "Otro" cuando no es una categoría válida.
     */
    public function etiquetaCategoria(): string
    {
        $categoria = trim((string) $this->categoria);

        return in_array($categoria, self::CATEGORIAS, true) ? $categoria : 'Otro';
    }

    /**
     * Dueño del gasto.
     *
     * @return BelongsTo<User, GastoHormiga>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hogar al que se cargó el gasto. Si es null, el gasto es personal.
     *
     * @return BelongsTo<Familia, GastoHormiga>
     */
    public function familia(): BelongsTo
    {
        return $this->belongsTo(Familia::class);
    }

    /**
     * Cuenta con la que se pagó (el dinero de la abuelita, el gas, la general).
     *
     * @return BelongsTo<Cuenta, GastoHormiga>
     */
    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    /**
     * Solo los gastos personales (sin familia).
     *
     * @param  Builder<GastoHormiga>  $query
     * @return Builder<GastoHormiga>
     */
    public function scopePersonales(Builder $query): Builder
    {
        return $query->whereNull('familia_id');
    }

    /**
     * Solo los gastos compartidos de un hogar.
     *
     * @param  Builder<GastoHormiga>  $query
     * @return Builder<GastoHormiga>
     */
    public function scopeDelHogar(Builder $query, int $familiaId): Builder
    {
        return $query->where('familia_id', $familiaId);
    }
}
