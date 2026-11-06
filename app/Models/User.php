<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Cada usuario nuevo recibe su cuenta "General" para poder anotar desde
     * el primer minuto.
     */
    protected static function booted(): void
    {
        static::created(function (User $user): void {
            $user->cuentaGeneral();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Gastos hormiga del usuario.
     *
     * @return HasMany<GastoHormiga, User>
     */
    public function gastos(): HasMany
    {
        return $this->hasMany(GastoHormiga::class);
    }

    /**
     * Hogares a los que pertenece (hoy la app permite uno por persona).
     *
     * @return BelongsToMany<Familia, User>
     */
    public function familias(): BelongsToMany
    {
        return $this->belongsToMany(Familia::class, 'familia_user')->withTimestamps();
    }

    /**
     * El hogar del usuario, si tiene uno.
     */
    public function familia(): ?Familia
    {
        return $this->familias()->first();
    }

    /**
     * Cuentas personales del usuario.
     *
     * @return HasMany<Cuenta, User>
     */
    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class)->whereNull('familia_id');
    }

    /**
     * Cuenta "General" del usuario, creándola si hiciera falta.
     */
    public function cuentaGeneral(): Cuenta
    {
        return $this->cuentas()->firstOrCreate(
            ['nombre' => 'General'],
            ['familia_id' => null],
        );
    }
}
