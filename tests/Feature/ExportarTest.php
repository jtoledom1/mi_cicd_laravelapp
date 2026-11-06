<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Familia;
use App\Models\GastoHormiga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Pruebas de las exportaciones: PDF (dompdf) y Excel (OpenSpout).
 *
 * Ambas descargas comparten los filtros del listado (`mes`, `categoria`,
 * `cuenta` y `ambito`). Aquí se comprueba el acceso, los tipos de contenido,
 * el nombre del archivo y, cuando hace falta, el total con el que se renderizó
 * la vista del PDF (espiando los datos de la vista, sin leer el binario).
 *
 * La base de datos de tests es SQLite en memoria (ver phpunit.xml).
 */
class ExportarTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------------

    /** Crea un usuario de prueba (nace con su cuenta "General"). */
    private function usuario(array $atributos = []): User
    {
        return User::factory()->create($atributos);
    }

    /** Crea un gasto del usuario indicado. */
    private function gastoDe(User $usuario, array $atributos = []): GastoHormiga
    {
        return GastoHormiga::factory()->create(array_merge([
            'user_id' => $usuario->id,
            'descripcion' => 'Gasto de prueba',
            'monto' => 10.00,
            'categoria' => 'Café',
            'fecha' => now()->toDateString(),
            'notas' => null,
        ], $atributos));
    }

    /**
     * Espía los datos con los que se renderiza la vista `exportar.gastos`
     * (la que dompdf convierte a PDF) sin tocar el binario.
     *
     * @return object{datos: array<string, mixed>|null}
     */
    private function espiarVistaDelPdf(): object
    {
        $captura = (object) ['datos' => null];

        View::composer('exportar.gastos', function ($vista) use ($captura): void {
            $captura->datos = $vista->getData();
        });

        return $captura;
    }

    // -----------------------------------------------------------------------
    // 1-4. Acceso y tipos de contenido
    // -----------------------------------------------------------------------

    public function test_un_invitado_no_puede_descargar_el_pdf(): void
    {
        $this->get(route('exportar.pdf'))->assertRedirect(route('login'));
    }

    public function test_un_invitado_no_puede_descargar_el_excel(): void
    {
        $this->get(route('exportar.excel'))->assertRedirect(route('login'));
    }

    public function test_el_pdf_responde_con_el_tipo_de_contenido_de_pdf(): void
    {
        $usuario = $this->usuario();
        $this->gastoDe($usuario);

        $this->actingAs($usuario)
            ->get(route('exportar.pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('gastos-hormiga-personal-historico.pdf');
    }

    public function test_el_excel_responde_con_el_tipo_de_contenido_de_xlsx(): void
    {
        $usuario = $this->usuario();
        $this->gastoDe($usuario);

        $this->actingAs($usuario)
            ->get(route('exportar.excel'))
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            )
            ->assertDownload('gastos-hormiga-personal-historico.xlsx');
    }

    // -----------------------------------------------------------------------
    // 5-10. Datos y filtros
    // -----------------------------------------------------------------------

    public function test_el_pdf_y_el_excel_se_generan_con_gastos(): void
    {
        $usuario = $this->usuario();
        $this->gastoDe($usuario, ['descripcion' => 'Café con leche', 'monto' => 25.50]);

        $captura = $this->espiarVistaDelPdf();

        $this->actingAs($usuario)
            ->get(route('exportar.pdf'))
            ->assertOk()
            ->assertDownload('gastos-hormiga-personal-historico.pdf');

        $this->assertNotNull($captura->datos, 'La vista del PDF debió renderizarse al menos una vez.');
        $this->assertSame(1, $captura->datos['cantidad']);
        $this->assertEqualsWithDelta(25.50, (float) $captura->datos['total'], 0.001);

        $this->actingAs($usuario)
            ->get(route('exportar.excel'))
            ->assertOk()
            ->assertDownload('gastos-hormiga-personal-historico.xlsx');
    }

    public function test_el_export_respeta_el_filtro_por_mes(): void
    {
        $usuario = $this->usuario();

        $this->gastoDe($usuario, [
            'descripcion' => 'Gasto de marzo',
            'monto' => 25.00,
            'fecha' => '2024-03-10',
        ]);
        $this->gastoDe($usuario, [
            'descripcion' => 'Gasto de abril',
            'monto' => 99.00,
            'fecha' => '2024-04-02',
        ]);

        $captura = $this->espiarVistaDelPdf();

        $this->actingAs($usuario)
            ->get(route('exportar.pdf', ['mes' => '2024-03']))
            ->assertOk()
            ->assertDownload('gastos-hormiga-personal-2024-03.pdf');

        $this->assertNotNull($captura->datos);
        $this->assertSame(1, $captura->datos['cantidad']);
        $this->assertEqualsWithDelta(25.00, (float) $captura->datos['total'], 0.001);
    }

    public function test_el_export_con_ambito_familia_solo_incluye_los_gastos_del_hogar(): void
    {
        $propietario = $this->usuario(['name' => 'Usuario de Prueba', 'email' => 'dueno@example.com']);
        $miembro = $this->usuario(['name' => 'Ana', 'email' => 'ana@example.com']);

        $familia = Familia::create([
            'nombre' => 'Casa Solar',
            'propietario_id' => $propietario->id,
            'codigo_invitacion' => 'FAM12345',
        ]);
        $familia->miembros()->attach([$propietario->id, $miembro->id]);
        $cuenta = $familia->cuentaGeneral();

        $this->gastoDe($miembro, [
            'descripcion' => 'Despensa del hogar',
            'monto' => 120.00,
            'familia_id' => $familia->id,
            'cuenta_id' => $cuenta->id,
        ]);
        $this->gastoDe($miembro, [
            'descripcion' => 'Café personal',
            'monto' => 30.00,
        ]);

        $captura = $this->espiarVistaDelPdf();

        $this->actingAs($miembro)
            ->get(route('exportar.pdf', ['ambito' => 'familia']))
            ->assertOk()
            ->assertDownload('gastos-hormiga-familia-historico.pdf');

        $this->assertNotNull($captura->datos);
        $this->assertSame(1, $captura->datos['cantidad']);
        $this->assertEqualsWithDelta(120.00, (float) $captura->datos['total'], 0.001);
    }

    public function test_el_export_sin_gastos_responde_200_igualmente(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->get(route('exportar.pdf'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($usuario)
            ->get(route('exportar.excel'))
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
    }

    public function test_sin_hogar_el_ambito_familia_cae_a_personal_y_no_falla(): void
    {
        $usuario = $this->usuario();
        $this->gastoDe($usuario, ['descripcion' => 'Gasto personal', 'monto' => 10.00]);

        $captura = $this->espiarVistaDelPdf();

        $this->actingAs($usuario)
            ->get(route('exportar.pdf', ['ambito' => 'familia']))
            ->assertOk()
            ->assertDownload('gastos-hormiga-personal-historico.pdf');

        $this->assertNotNull($captura->datos);
        $this->assertSame(1, $captura->datos['cantidad']);
        $this->assertEqualsWithDelta(10.00, (float) $captura->datos['total'], 0.001);
    }

    public function test_el_filtro_por_cuenta_acota_el_resultado(): void
    {
        $usuario = $this->usuario();

        $gas = Cuenta::create([
            'nombre' => 'Gas',
            'user_id' => $usuario->id,
            'familia_id' => null,
        ]);
        $despensa = Cuenta::create([
            'nombre' => 'Despensa',
            'user_id' => $usuario->id,
            'familia_id' => null,
        ]);

        $this->gastoDe($usuario, [
            'descripcion' => 'Tanque de gas',
            'monto' => 80.00,
            'cuenta_id' => $gas->id,
        ]);
        $this->gastoDe($usuario, [
            'descripcion' => 'Súper de la semana',
            'monto' => 50.00,
            'cuenta_id' => $despensa->id,
        ]);

        $captura = $this->espiarVistaDelPdf();

        $this->actingAs($usuario)
            ->get(route('exportar.pdf', ['cuenta' => $gas->id]))
            ->assertOk()
            ->assertDownload('gastos-hormiga-personal-historico-Gas.pdf');

        $this->assertNotNull($captura->datos);
        $this->assertSame(1, $captura->datos['cantidad']);
        $this->assertEqualsWithDelta(80.00, (float) $captura->datos['total'], 0.001);
    }
}
