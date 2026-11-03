<?php

namespace Database\Seeders;

use App\Models\GastoHormiga;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Siembra la base de datos de "Gastos Hormiga".
     */
    public function run(): void
    {
        // Usuario de prueba: test@example.com / password.
        User::factory()->create([
            'name' => 'Usuario de Prueba',
            'email' => 'test@example.com',
        ]);

        // Gastos distribuidos en los últimos 30 días.
        GastoHormiga::factory()->count(36)->create();

        // Algunos gastos de hoy para que el resumen diario no salga en cero.
        GastoHormiga::factory()->count(4)->create([
            'fecha' => today(),
        ]);
    }
}
