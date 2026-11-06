<?php

namespace Tests\Feature;

use App\Models\GastoHormiga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de las dos pantallas de resumen: mensual y anual.
 *
 * Lo importante aquí es que cada usuario vea únicamente sus propios datos y
 * que los totales se calculen sobre el mes o el año pedido. La base de datos
 * de tests es SQLite en memoria (ver phpunit.xml).
 */
class ResumenTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------------

    /** Crea un usuario con gastos independientes. */
    private function usuario(array $atributos = []): User
    {
        return User::factory()->create($atributos);
    }

    /** Crea un gasto que pertenece al usuario indicado. */
    private function gastoDe(User $user, array $atributos = []): GastoHormiga
    {
        return GastoHormiga::factory()->for($user)->create(array_merge([
            'descripcion' => 'Gasto de prueba',
            'monto' => 10.00,
            'categoria' => 'Café',
            'fecha' => now()->toDateString(),
            'notas' => null,
        ], $atributos));
    }

    /** Primer día del mes actual, para fechas que siempre caen en él. */
    private function inicioDeEsteMes(): string
    {
        return now()->startOfMonth()->toDateString();
    }

    // -----------------------------------------------------------------------
    // 1. Invitados
    // -----------------------------------------------------------------------

    public function test_un_invitado_no_puede_ver_los_resumenes(): void
    {
        $this->get(route('resumen.mensual'))->assertRedirect(route('login'));
        $this->get(route('resumen.anual'))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------------
    // 2-7. Resumen mensual
    // -----------------------------------------------------------------------

    public function test_el_resumen_mensual_se_renderiza_para_un_usuario_con_gastos(): void
    {
        $user = $this->usuario();
        $this->gastoDe($user, ['descripcion' => 'Café de la mañana', 'monto' => 25.50]);

        $this->actingAs($user)
            ->get(route('resumen.mensual'))
            ->assertOk()
            ->assertViewIs('resumen.mensual')
            ->assertViewHas('total')
            ->assertViewHas('porCategoria');
    }

    public function test_el_total_mensual_suma_solo_los_gastos_del_usuario_autenticado(): void
    {
        $user = $this->usuario();
        $otro = $this->usuario(['email' => 'otro@example.com']);

        // 60 + 40 del usuario autenticado; 500 de otra cuenta.
        $this->gastoDe($user, ['monto' => 60.00, 'fecha' => $this->inicioDeEsteMes()]);
        $this->gastoDe($user, ['monto' => 40.00, 'fecha' => now()->toDateString()]);
        $this->gastoDe($otro, ['monto' => 500.00, 'fecha' => now()->toDateString()]);

        $respuesta = $this->actingAs($user)->get(route('resumen.mensual'));

        $respuesta->assertOk()->assertViewIs('resumen.mensual');
        $this->assertViewHasTotal($respuesta, 100.0);
        $this->assertSame(2, $respuesta->viewData('cantidad'));
    }

    public function test_el_resumen_mensual_ignora_los_gastos_de_otro_mes(): void
    {
        $user = $this->usuario();

        $this->gastoDe($user, ['monto' => 30.00, 'fecha' => now()->toDateString()]);
        $this->gastoDe($user, [
            'monto' => 200.00,
            'fecha' => now()->startOfMonth()->subMonth()->toDateString(),
        ]);

        $respuesta = $this->actingAs($user)->get(route('resumen.mensual'));

        $respuesta->assertOk();
        $this->assertViewHasTotal($respuesta, 30.0);
        $this->assertEqualsWithDelta(200.0, (float) $respuesta->viewData('totalAnterior'), 0.001);
    }

    public function test_el_resumen_mensual_por_defecto_usa_el_mes_actual(): void
    {
        $user = $this->usuario();
        $this->gastoDe($user, ['monto' => 12.00, 'fecha' => now()->toDateString()]);

        $respuesta = $this->actingAs($user)->get(route('resumen.mensual'));

        $respuesta->assertOk();
        $this->assertSame(now()->format('Y-m'), $respuesta->viewData('mes'));
        $this->assertSame((int) now()->year, $respuesta->viewData('anio'));
        $this->assertSame((int) now()->month, $respuesta->viewData('numeroMes'));
        $this->assertTrue($respuesta->viewData('esMesActual'));
    }

    public function test_el_resumen_mensual_acepta_el_parametro_mes(): void
    {
        $user = $this->usuario();
        $this->gastoDe($user, ['monto' => 25.00, 'fecha' => '2024-03-10']);
        $this->gastoDe($user, ['monto' => 99.00, 'fecha' => '2024-04-02']);

        $respuesta = $this->actingAs($user)
            ->get(route('resumen.mensual', ['mes' => '2024-03']));

        $respuesta->assertOk();
        $this->assertSame('2024-03', $respuesta->viewData('mes'));
        $this->assertSame(2024, $respuesta->viewData('anio'));
        $this->assertSame(3, $respuesta->viewData('numeroMes'));
        $this->assertSame('marzo de 2024', $respuesta->viewData('nombreMes'));
        $this->assertViewHasTotal($respuesta, 25.0);
    }

    public function test_el_reparto_por_categoria_agrupa_y_suma(): void
    {
        $user = $this->usuario();

        $this->gastoDe($user, [
            'monto' => 30.00,
            'categoria' => 'Café',
            'fecha' => $this->inicioDeEsteMes(),
        ]);
        $this->gastoDe($user, [
            'monto' => 20.00,
            'categoria' => 'Café',
            'fecha' => now()->toDateString(),
        ]);
        $this->gastoDe($user, [
            'monto' => 15.50,
            'categoria' => 'Transporte',
            'fecha' => now()->toDateString(),
        ]);

        $respuesta = $this->actingAs($user)->get(route('resumen.mensual'));
        $porCategoria = $respuesta->viewData('porCategoria');

        $respuesta->assertOk();
        $this->assertCount(2, $porCategoria);
        $this->assertEqualsCanonicalizing(
            ['Café', 'Transporte'],
            $porCategoria->pluck('categoria')->all()
        );

        $cafe = $porCategoria->firstWhere('categoria', 'Café');
        $transporte = $porCategoria->firstWhere('categoria', 'Transporte');

        $this->assertEqualsWithDelta(50.0, (float) $cafe->total, 0.001);
        $this->assertSame(2, $cafe->cantidad);
        $this->assertEqualsWithDelta(15.50, (float) $transporte->total, 0.001);
        $this->assertSame(1, $transporte->cantidad);
        $this->assertEqualsWithDelta(76.34, (float) $cafe->parte, 0.01);
        $this->assertViewHasTotal($respuesta, 65.50);
    }

    // -----------------------------------------------------------------------
    // 8-12. Resumen anual
    // -----------------------------------------------------------------------

    public function test_el_resumen_anual_suma_los_doce_meses_del_anio(): void
    {
        $user = $this->usuario();

        $this->gastoDe($user, ['monto' => 10.00, 'fecha' => '2024-01-10']);
        $this->gastoDe($user, ['monto' => 20.00, 'fecha' => '2024-06-15']);
        $this->gastoDe($user, ['monto' => 30.50, 'fecha' => '2024-12-31']);

        $respuesta = $this->actingAs($user)
            ->get(route('resumen.anual', ['anio' => 2024]));

        $respuesta->assertOk()->assertViewIs('resumen.anual');
        $this->assertSame(2024, $respuesta->viewData('anio'));
        $this->assertViewHasTotal($respuesta, 60.5);
        $this->assertSame(3, $respuesta->viewData('cantidad'));
        $this->assertEqualsWithDelta(10.0, (float) $respuesta->viewData('porMes')[1], 0.001);
        $this->assertEqualsWithDelta(20.0, (float) $respuesta->viewData('porMes')[6], 0.001);
        $this->assertEqualsWithDelta(30.5, (float) $respuesta->viewData('porMes')[12], 0.001);
    }

    public function test_el_resumen_anual_usa_el_anio_actual_por_defecto_y_acepta_el_parametro(): void
    {
        $user = $this->usuario();
        $this->gastoDe($user, ['monto' => 18.00, 'fecha' => now()->toDateString()]);

        $porDefecto = $this->actingAs($user)->get(route('resumen.anual'));
        $porDefecto->assertOk();
        $this->assertSame((int) now()->year, $porDefecto->viewData('anio'));

        $pedido = $this->actingAs($user)
            ->get(route('resumen.anual', ['anio' => 2024]));
        $pedido->assertOk();
        $this->assertSame(2024, $pedido->viewData('anio'));
    }

    public function test_el_resumen_anual_no_mezcla_datos_de_otro_usuario(): void
    {
        $user = $this->usuario();
        $otro = $this->usuario(['email' => 'otro@example.com']);

        $this->gastoDe($user, ['monto' => 10.00, 'fecha' => '2024-05-05']);
        $this->gastoDe($otro, ['monto' => 500.00, 'fecha' => '2024-05-06']);

        $respuesta = $this->actingAs($user)
            ->get(route('resumen.anual', ['anio' => 2024]));

        $respuesta->assertOk();
        $this->assertViewHasTotal($respuesta, 10.0);
    }

    public function test_un_anio_sin_datos_responde_con_total_cero(): void
    {
        $user = $this->usuario();

        $respuesta = $this->actingAs($user)
            ->get(route('resumen.anual', ['anio' => 2001]));

        $respuesta->assertOk()->assertViewIs('resumen.anual');
        $this->assertSame(0.0, $respuesta->viewData('total'));
        $this->assertSame(0, $respuesta->viewData('cantidad'));
        $this->assertSame(range(1, 12), array_keys($respuesta->viewData('porMes')));
    }

    public function test_el_total_anual_es_float_y_el_reparto_tiene_doce_meses(): void
    {
        $user = $this->usuario();
        $this->gastoDe($user, ['monto' => 33.33, 'fecha' => '2024-09-09']);

        $respuesta = $this->actingAs($user)
            ->get(route('resumen.anual', ['anio' => 2024]));

        $respuesta->assertOk();
        $this->assertIsFloat($respuesta->viewData('total'));
        $this->assertCount(12, $respuesta->viewData('porMes'));
        $this->assertSame(range(1, 12), array_keys($respuesta->viewData('porMes')));
        $this->assertEqualsWithDelta(
            (float) $respuesta->viewData('total'),
            (float) array_sum($respuesta->viewData('porMes')),
            0.001
        );
        $this->assertEqualsWithDelta(33.33, (float) $respuesta->viewData('total'), 0.001);
    }

    // -----------------------------------------------------------------------
    // Utilidad de aserción
    // -----------------------------------------------------------------------

    /**
     * Comprueba el total de un resumen aceptando el pequeño margen de los
     * números en punto flotante.
     */
    private function assertViewHasTotal(\Illuminate\Testing\TestResponse $respuesta, float $esperado): void
    {
        $total = $respuesta->viewData('total');

        $this->assertIsFloat($total);
        $this->assertEqualsWithDelta($esperado, $total, 0.001);
    }
}
