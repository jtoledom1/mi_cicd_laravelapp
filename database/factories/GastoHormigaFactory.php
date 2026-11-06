<?php

namespace Database\Factories;

use App\Models\GastoHormiga;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GastoHormiga>
 */
class GastoHormigaFactory extends Factory
{
    /**
     * Modelo asociado a la fábrica.
     *
     * @var class-string<GastoHormiga>
     */
    protected $model = GastoHormiga::class;

    /**
     * Define el estado por defecto de un gasto hormiga.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categoria = fake()->randomElement(GastoHormiga::CATEGORIAS);
        [$montoMinimo, $montoMaximo] = $this->rangoDeMonto($categoria);

        return [
            'user_id' => User::factory(),
            'descripcion' => fake()->randomElement($this->descripcionesPorCategoria()[$categoria]),
            'monto' => fake()->randomFloat(2, $montoMinimo, $montoMaximo),
            'categoria' => $categoria,
            'fecha' => fake()->dateTimeBetween('-30 days', 'now'),
            'notas' => fake()->boolean(55) ? fake()->randomElement($this->notasPosibles()) : null,
        ];
    }

    /**
     * Descripciones realistas en español mexicano para cada categoría.
     *
     * @return array<string, list<string>>
     */
    private function descripcionesPorCategoria(): array
    {
        return [
            'Café' => [
                'Café de olla en el puesto',
                'Latte de la esquina',
                'Americano de la cafetería',
                'Capuchino con pan dulce',
                'Café de máquina de la oficina',
                'Café con leche para llevar',
            ],
            'Antojo' => [
                'Tacos al pastor',
                'Quesadillas de la esquina',
                'Tamales de la mañana',
                'Elote con todo',
                'Churros con chocolate',
                'Gorditas de chicharrón',
                'Pozole de la fonda',
                'Flautas de la tarde',
            ],
            'Transporte' => [
                'Pasaje de camión',
                'Metro ida y vuelta',
                'Taxi por flojera',
                'Gasolina para la moto',
                'Combi al mercado',
                'Estacionamiento del centro',
                'Bici pública',
            ],
            'Botana' => [
                'Sabritas y refresco',
                'Dulces de la tiendita',
                'Cacahuates japoneses',
                'Galletas con café',
                'Papas y un Boing',
                'Palomitas para la película',
                'Hielitos de la esquina',
            ],
            'Suscripción' => [
                'Netflix mensual',
                'Spotify Premium',
                'Plan de datos del cel',
                'Amazon Prime',
                'Crunchyroll',
                'YouTube Premium',
                'Almacenamiento en la nube',
            ],
            'Otro' => [
                'Estampita para el cel',
                'Paraguas improvisado',
                'Cargador de repuesto',
                'Regalo de cumpleaños',
                'Feria del tianguis',
                'Cuota de la tanda',
            ],
        ];
    }

    /**
     * Rango de monto en pesos mexicanos según la categoría.
     *
     * @return array{0: float, 1: float}
     */
    private function rangoDeMonto(string $categoria): array
    {
        return match ($categoria) {
            'Café' => [28.0, 85.0],
            'Antojo' => [35.0, 160.0],
            'Transporte' => [15.0, 120.0],
            'Botana' => [18.0, 90.0],
            'Suscripción' => [99.0, 180.0],
            default => [20.0, 180.0],
        };
    }

    /**
     * Notas cortas para darle algo de contexto a los gastos.
     *
     * @return list<string>
     */
    private function notasPosibles(): array
    {
        return [
            'Se me antojó y ya.',
            'Para el hambre de la tarde.',
            'Me lo pidió mi hermana.',
            'No lo tenía planeado.',
            'Café de emergencia antes de la junta.',
            'Ya tenía hambre a media mañana.',
            'Lo compré en la tiendita de la esquina.',
            'Valió la pena.',
        ];
    }
}
