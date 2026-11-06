<?php

namespace Tests\Unit;

use App\Models\GastoHormiga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pruebas unitarias del modelo GastoHormiga.
 *
 * Nota: este test extiende Tests\TestCase (y no PHPUnit\Framework\TestCase)
 * porque el cast `date` y el scope `delMes` necesitan el contenedor de Laravel
 * y la base de datos SQLite en memoria definida en phpunit.xml. Por eso se usa
 * también RefreshDatabase: el scope se prueba contra la tabla real.
 */
class GastoHormigaModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crea un gasto de ejemplo con datos válidos por defecto.
     * Todo gasto necesita dueño: la columna user_id es obligatoria.
     */
    private function crearGasto(array $atributos = []): GastoHormiga
    {
        return GastoHormiga::factory()
            ->for(User::factory()->create())
            ->create(array_merge([
                'descripcion' => 'Café americano',
                'monto' => 25.50,
                'categoria' => 'Café',
                'fecha' => now()->toDateString(),
                'notas' => null,
            ], $atributos));
    }

    public function test_la_constante_categorias_contiene_cafe_y_otro(): void
    {
        $this->assertContains('Café', GastoHormiga::CATEGORIAS);
        $this->assertContains('Otro', GastoHormiga::CATEGORIAS);
        $this->assertContains('Suscripción', GastoHormiga::CATEGORIAS);
        $this->assertCount(6, GastoHormiga::CATEGORIAS);
    }

    public function test_el_cast_de_monto_devuelve_un_string_con_dos_decimales(): void
    {
        $gasto = $this->crearGasto(['monto' => 12.5]);

        $this->assertIsString($gasto->monto);
        $this->assertSame('12.50', $gasto->monto);
        // También al releer desde la base de datos.
        $this->assertSame('12.50', $gasto->fresh()->monto);
    }

    public function test_el_cast_de_fecha_devuelve_carbon(): void
    {
        $gasto = $this->crearGasto(['fecha' => '2026-01-15']);

        $this->assertInstanceOf(Carbon::class, $gasto->fecha);
        $this->assertSame('2026-01-15', $gasto->fecha->toDateString());
        $this->assertSame('00:00:00', $gasto->fecha->format('H:i:s'));
    }

    public function test_scope_del_mes_filtra_por_mes(): void
    {
        $this->crearGasto(['descripcion' => 'Enero inicio', 'fecha' => '2026-01-01']);
        $this->crearGasto(['descripcion' => 'Enero fin', 'fecha' => '2026-01-31']);
        $this->crearGasto(['descripcion' => 'Febrero', 'fecha' => '2026-02-10']);
        $this->crearGasto(['descripcion' => 'Diciembre previo', 'fecha' => '2025-12-31']);

        $deEnero = GastoHormiga::delMes('2026-01')->pluck('descripcion')->all();

        $this->assertEqualsCanonicalizing(['Enero inicio', 'Enero fin'], $deEnero);
        $this->assertSame(2, GastoHormiga::delMes('2026-01')->count());
        $this->assertSame(1, GastoHormiga::delMes('2026-02')->count());
        $this->assertSame(0, GastoHormiga::delMes('2026-03')->count());
    }
}
