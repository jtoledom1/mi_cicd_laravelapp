<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['nombre', 'user_id', 'familia_id'];

    /**
     * Dueño de la cuenta cuando es personal.
     *
     * @return BelongsTo<User, Cuenta>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hogar al que pertenece la cuenta cuando es compartida.
     *
     * @return BelongsTo<Familia, Cuenta>
     */
    public function familia(): BelongsTo
    {
        return $this->belongsTo(Familia::class);
    }

    /**
     * Gastos cargados a esta cuenta.
     *
     * @return HasMany<GastoHormiga, Cuenta>
     */
    public function gastos(): HasMany
    {
        return $this->hasMany(GastoHormiga::class);
    }

    /**
     * Cuentas personales de un usuario.
     *
     * @param  Builder<Cuenta>  $query
     * @return Builder<Cuenta>
     */
    public function scopePersonales(Builder $query, int $usuarioId): Builder
    {
        return $query->where('user_id', $usuarioId)->whereNull('familia_id');
    }

    /**
     * Cuentas de un hogar.
     *
     * @param  Builder<Cuenta>  $query
     * @return Builder<Cuenta>
     */
    public function scopeDelHogar(Builder $query, int $familiaId): Builder
    {
        return $query->where('familia_id', $familiaId);
    }

    /**
     * ¿La cuenta se comparte con la familia?
     */
    public function esDeFamilia(): bool
    {
        return $this->familia_id !== null;
    }
}
