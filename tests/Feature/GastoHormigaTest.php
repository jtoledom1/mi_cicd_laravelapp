<?php

namespace Tests\Feature;

use App\Models\GastoHormiga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del CRUD de "Gastos Hormiga": rutas, vistas, validación y filtros.
 *
 * La base de datos de tests es SQLite en memoria (ver phpunit.xml), así que
 * RefreshDatabase migra el esquema desde cero en cada test.
 */
class GastoHormigaTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------------

    /**
     * Crea un gasto de ejemplo con datos válidos por defecto.
     * Se apoya en la factory GastoHormigaFactory (formato estándar de Laravel).
     */
    private function crearGasto(array $atributos = []): GastoHormiga
    {
        return GastoHormiga::factory()->create(array_merge([
            'descripcion' => 'Café americano',
            'monto' => 25.50,
            'categoria' => 'Café',
            'fecha' => now()->toDateString(),
            'notas' => null,
        ], $atributos));
    }

    /**
     * Payload válido para store/update, con la posibilidad de sobrescribir
     * campos concretos en cada test.
     */
    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'descripcion' => 'Café con leche',
            'monto' => 22.50,
            'categoria' => 'Café',
            'fecha' => now()->toDateString(),
            'notas' => 'Con leche de avena',
        ], $sobrescribir);
    }

    /**
     * Fecha dentro del mes actual: día 4 del mes (siempre pasado o el día 4,
     * nunca del mes siguiente, sin importar en qué día se ejecute el test).
     */
    private function fechaDeEsteMes(): string
    {
        return now()->startOfMonth()->addDays(3)->toDateString();
    }

    /** Fecha dentro del mes anterior (mismo día 4, mes previo). */
    private function fechaDelMesPasado(): string
    {
        // startOfMonth() evita los días inexistentes (31 de febrero, etc.).
        return now()->startOfMonth()->subMonth()->addDays(3)->toDateString();
    }

    // -----------------------------------------------------------------------
    // 1. Raíz
    // -----------------------------------------------------------------------

    public function test_la_raiz_redirige_al_index_de_gastos(): void
    {
        $this->get('/')->assertRedirect(route('gastos.index'));
    }

    // -----------------------------------------------------------------------
    // 2-3. Index
    // -----------------------------------------------------------------------

    public function test_el_index_se_renderiza_y_lista_los_gastos(): void
    {
        $this->crearGasto(['descripcion' => 'Café con leche']);

        $this->get(route('gastos.index'))
            ->assertOk()
            ->assertViewIs('gastos.index')
            ->assertSee('Café con leche');
    }

    public function test_el_index_calcula_el_total_solo_del_mes_actual(): void
    {
        // Dos gastos del mes actual: 25.50 + 30.00 = 55.50.
        $this->crearGasto([
            'descripcion' => 'Gasto de hoy',
            'monto' => 25.50,
            'fecha' => now()->toDateString(),
        ]);
        $this->crearGasto([
            'descripcion' => 'Botana del mes',
            'monto' => 30.00,
            'fecha' => $this->fechaDeEsteMes(),
        ]);

        // Un gasto del mes pasado que NO debe sumarse al total.
        $this->crearGasto([
            'descripcion' => 'Gasto del mes pasado',
            'monto' => 100.00,
            'fecha' => $this->fechaDelMesPasado(),
        ]);

        $response = $this->get(route('gastos.index'));

        $response->assertOk()->assertViewIs('gastos.index');

        // El total del mes debe excluir los 100.00 del mes anterior.
        $this->assertEqualsWithDelta(
            55.50,
            (float) $response->viewData('totalMes'),
            0.001,
            'El total del mes solo debe sumar los gastos del mes actual.'
        );

        // Y ese total debe verse en pantalla (se aceptan formatos 55.50 / 55,50).
        $html = (string) $response->getContent();

        $this->assertTrue(
            str_contains($html, '55.50') || str_contains($html, '55,50'),
            'La vista index debe mostrar el total del mes (55.50).'
        );
        $this->assertStringNotContainsString('155.50', $html);
        $this->assertStringNotContainsString('155,50', $html);
    }

    // -----------------------------------------------------------------------
    // 4-9. Create / Store y validación
    // -----------------------------------------------------------------------

    public function test_el_formulario_de_create_se_renderiza(): void
    {
        $this->get(route('gastos.create'))
            ->assertOk()
            ->assertViewIs('gastos.create')
            ->assertSee('descripcion');
    }

    public function test_store_crea_un_gasto_y_redirige_al_index_con_status(): void
    {
        $response = $this->post(route('gastos.store'), $this->datosValidos([
            'descripcion' => 'Café de la esquina',
            'monto' => 18.50,
        ]));

        $response->assertRedirect(route('gastos.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('gastos_hormiga', [
            'descripcion' => 'Café de la esquina',
            'categoria' => 'Café',
        ]);
        $this->assertDatabaseCount('gastos_hormiga', 1);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->from(route('gastos.create'))
            ->post(route('gastos.store'), [])
            ->assertSessionHasErrors(['descripcion', 'monto', 'categoria', 'fecha']);

        $this->assertDatabaseCount('gastos_hormiga', 0);
    }

    public function test_store_rechaza_un_monto_cero_o_negativo(): void
    {
        foreach ([0, -10.50] as $montoInvalido) {
            $this->from(route('gastos.create'))
                ->post(route('gastos.store'), $this->datosValidos(['monto' => $montoInvalido]))
                ->assertSessionHasErrors(['monto']);
        }

        $this->assertDatabaseCount('gastos_hormiga', 0);
    }

    public function test_store_rechaza_una_fecha_futura(): void
    {
        $this->from(route('gastos.create'))
            ->post(route('gastos.store'), $this->datosValidos([
                'fecha' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['fecha']);

        $this->assertDatabaseCount('gastos_hormiga', 0);
    }

    public function test_store_rechaza_una_categoria_inexistente(): void
    {
        $this->from(route('gastos.create'))
            ->post(route('gastos.store'), $this->datosValidos([
                'categoria' => 'Criptomonedas',
            ]))
            ->assertSessionHasErrors(['categoria']);

        $this->assertDatabaseCount('gastos_hormiga', 0);
    }

    // -----------------------------------------------------------------------
    // 10-11. Show / Edit
    // -----------------------------------------------------------------------

    public function test_show_se_renderiza_para_un_gasto_existente(): void
    {
        $gasto = $this->crearGasto(['descripcion' => 'Botana de la tarde']);

        $this->get(route('gastos.show', $gasto))
            ->assertOk()
            ->assertViewIs('gastos.show')
            ->assertSee('Botana de la tarde');
    }

    public function test_el_formulario_de_edit_se_renderiza_con_los_datos_del_gasto(): void
    {
        $gasto = $this->crearGasto(['descripcion' => 'Suscripción de música']);

        $this->get(route('gastos.edit', $gasto))
            ->assertOk()
            ->assertViewIs('gastos.edit')
            ->assertSee('Suscripción de música')
            ->assertSee('descripcion');
    }

    // -----------------------------------------------------------------------
    // 12-13. Update / Destroy
    // -----------------------------------------------------------------------

    public function test_update_modifica_el_gasto_y_redirige(): void
    {
        $gasto = $this->crearGasto(['descripcion' => 'Botana pequeña', 'monto' => 10.00]);

        $response = $this->put(route('gastos.update', $gasto), $this->datosValidos([
            'descripcion' => 'Botana grande',
            'monto' => 30.00,
            'categoria' => 'Botana',
            'notas' => 'Actualizado desde el test',
        ]));

        $response->assertRedirect();
        $this->assertContains(
            $response->headers->get('Location'),
            [route('gastos.index'), route('gastos.show', $gasto)],
            'update debe redirigir al index o al detalle del gasto.'
        );

        $gasto->refresh();

        $this->assertSame('Botana grande', $gasto->descripcion);
        $this->assertSame('Botana', $gasto->categoria);
        $this->assertEqualsWithDelta(30.00, (float) $gasto->monto, 0.001);
        $this->assertSame(1, GastoHormiga::count());
    }

    public function test_destroy_elimina_el_gasto_y_redirige(): void
    {
        $gasto = $this->crearGasto(['descripcion' => 'Gasto que se va a borrar']);
        $this->crearGasto(['descripcion' => 'Gasto que se queda']);

        $this->delete(route('gastos.destroy', $gasto))->assertRedirect();

        $this->assertDatabaseMissing('gastos_hormiga', ['id' => $gasto->id]);
        $this->assertDatabaseCount('gastos_hormiga', 1);
    }

    // -----------------------------------------------------------------------
    // 14-15. Filtros del index
    // -----------------------------------------------------------------------

    public function test_el_filtro_por_categoria_solo_muestra_los_gastos_de_esa_categoria(): void
    {
        $this->crearGasto(['descripcion' => 'Café con leche', 'categoria' => 'Café']);
        $this->crearGasto(['descripcion' => 'Uber al trabajo', 'categoria' => 'Transporte']);

        $this->get(route('gastos.index', ['categoria' => 'Café']))
            ->assertOk()
            ->assertSee('Café con leche')
            ->assertDontSee('Uber al trabajo');
    }

    public function test_el_filtro_por_mes_solo_muestra_los_gastos_de_ese_mes(): void
    {
        $mesAnterior = now()->startOfMonth()->subMonth()->format('Y-m');

        $this->crearGasto([
            'descripcion' => 'Café del mes actual',
            'fecha' => $this->fechaDeEsteMes(),
        ]);
        $this->crearGasto([
            'descripcion' => 'Café del mes anterior',
            'fecha' => $this->fechaDelMesPasado(),
        ]);

        $this->get(route('gastos.index', ['mes' => $mesAnterior]))
            ->assertOk()
            ->assertSee('Café del mes anterior')
            ->assertDontSee('Café del mes actual');
    }
}
