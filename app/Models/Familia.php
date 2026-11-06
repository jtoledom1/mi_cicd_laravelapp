<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Familia extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla (el plural español no sigue la convención de Laravel).
     *
     * @var string
     */
    protected $table = 'familias';

    /**
     * @var list<string>
     */
    protected $fillable = ['nombre', 'propietario_id', 'codigo_invitacion'];

    /**
     * Cada hogar nace con su cuenta "General".
     */
    protected static function booted(): void
    {
        static::created(function (Familia $familia): void {
            $familia->cuentaGeneral();
        });
    }

    /**
     * Quien creó el hogar.
     *
     * @return BelongsTo<User, Familia>
     */
    public function propietario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'propietario_id');
    }

    /**
     * Personas que comparten los gastos.
     *
     * @return BelongsToMany<User, Familia>
     */
    public function miembros(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'familia_user')->withTimestamps();
    }

    /**
     * Cuentas propias del hogar (el gas, la despensa, la general...).
     *
     * @return HasMany<Cuenta, Familia>
     */
    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }

    /**
     * Gastos compartidos del hogar.
     *
     * @return HasMany<GastoHormiga, Familia>
     */
    public function gastos(): HasMany
    {
        return $this->hasMany(GastoHormiga::class);
    }

    /**
     * Código corto para que otra persona se una al hogar.
     */
    public static function generarCodigo(): string
    {
        do {
            $codigo = Str::upper(Str::random(8));
        } while (static::query()->where('codigo_invitacion', $codigo)->exists());

        return $codigo;
    }

    /**
     * Cuenta "General" del hogar, creándola si hiciera falta.
     */
    public function cuentaGeneral(): Cuenta
    {
        return $this->cuentas()->firstOrCreate(
            ['nombre' => 'General'],
            ['user_id' => null],
        );
    }
}
