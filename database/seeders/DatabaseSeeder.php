<?php

namespace Database\Seeders;

use App\Models\Familia;
use App\Models\GastoHormiga;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Siembra la base de datos de "Gastos Hormiga" con un hogar de ejemplo.
     */
    public function run(): void
    {
        // Dos cuentas: la principal y la de Ana, que comparten hogar.
        $prueba = User::factory()->create([
            'name' => 'Usuario de Prueba',
            'email' => 'test@example.com',
        ]);

        $ana = User::factory()->create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        $prueba->cuentaGeneral();
        $ana->cuentaGeneral();

        // El hogar, con las dos dentro.
        $hogar = Familia::create([
            'nombre' => 'Casa Solar',
            'propietario_id' => $prueba->id,
            'codigo_invitacion' => Familia::generarCodigo(),
        ]);

        $hogar->miembros()->attach([$prueba->id, $ana->id]);

        $generalHogar = $hogar->cuentaGeneral();
        $gas = $hogar->cuentas()->firstOrCreate(['nombre' => 'Gas']);
        $despensa = $hogar->cuentas()->firstOrCreate(['nombre' => 'Despensa']);

        // Cuentas personales de ejemplo: el dinero de la abuelita y el lonche.
        $abuelita = $prueba->cuentas()->firstOrCreate(['nombre' => 'Dinero de mi abuelita']);
        $lonche = $prueba->cuentas()->firstOrCreate(['nombre' => 'Lonche de la semana']);

        $generalPrueba = $prueba->cuentaGeneral();
        $generalAna = $ana->cuentaGeneral();

        // Gastos personales de la cuenta principal.
        foreach ([[$generalPrueba, 24], [$abuelita, 8], [$lonche, 6]] as [$cuenta, $cantidad]) {
            GastoHormiga::factory()
                ->count($cantidad)
                ->create([
                    'user_id' => $prueba->id,
                    'familia_id' => null,
                    'cuenta_id' => $cuenta->id,
                ]);
        }

        GastoHormiga::factory()
            ->count(4)
            ->create([
                'user_id' => $prueba->id,
                'familia_id' => null,
                'cuenta_id' => $generalPrueba->id,
                'fecha' => today(),
            ]);

        // Gastos personales de Ana.
        GastoHormiga::factory()
            ->count(12)
            ->create([
                'user_id' => $ana->id,
                'familia_id' => null,
                'cuenta_id' => $generalAna->id,
            ]);

        // Gastos compartidos del hogar, repartidos entre las cuentas familiares.
        $cuentasHogar = [$generalHogar, $gas, $despensa];

        foreach ($cuentasHogar as $indice => $cuenta) {
            GastoHormiga::factory()
                ->count(6)
                ->create([
                    'user_id' => $indice % 2 === 0 ? $prueba->id : $ana->id,
                    'familia_id' => $hogar->id,
                    'cuenta_id' => $cuenta->id,
                ]);
        }

        // Un par de gastos familiares de hoy para que el resumen no salga vacío.
        GastoHormiga::factory()
            ->count(2)
            ->create([
                'user_id' => $prueba->id,
                'familia_id' => $hogar->id,
                'cuenta_id' => $gas->id,
                'fecha' => today(),
            ]);
    }
}
