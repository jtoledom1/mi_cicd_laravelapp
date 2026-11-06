<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Familia;
use App\Models\GastoHormiga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de las cuentas: los "sobres" de dinero con los que se separan los
 * gastos (el dinero de la abuelita, el gas, la despensa, la general).
 *
 * Cada cuenta vive en un solo espacio: es personal (`user_id`) o del hogar
 * (`familia_id`). La cuenta "General" se crea sola con cada usuario y con
 * cada hogar; no se puede borrar y recibe los gastos de las cuentas que se
 * eliminan.
 *
 * La base de datos de tests es SQLite en memoria (ver phpunit.xml).
 */
class CuentaTest extends TestCase
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

    /** Crea un hogar con su propietario como único miembro. */
    private function hogar(User $propietario, array $atributos = []): Familia
    {
        $familia = Familia::create(array_merge([
            'nombre' => 'Casa Solar',
            'propietario_id' => $propietario->id,
            'codigo_invitacion' => 'ABC12345',
        ], $atributos));

        $familia->miembros()->attach($propietario->id);

        return $familia;
    }

    /** Crea una cuenta personal. */
    private function cuentaPersonal(User $usuario, string $nombre): Cuenta
    {
        return Cuenta::create([
            'nombre' => $nombre,
            'user_id' => $usuario->id,
            'familia_id' => null,
        ]);
    }

    /** Crea una cuenta del hogar. */
    private function cuentaDelHogar(Familia $familia, string $nombre): Cuenta
    {
        return Cuenta::create([
            'nombre' => $nombre,
            'user_id' => null,
            'familia_id' => $familia->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // 1-2. Acceso y listado
    // -----------------------------------------------------------------------

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->get(route('cuentas.index'))->assertRedirect(route('login'));
    }

    public function test_la_pantalla_lista_las_cuentas_del_usuario_incluida_la_general(): void
    {
        $usuario = $this->usuario();
        $this->cuentaPersonal($usuario, 'Dinero de mi abuelita');

        $this->actingAs($usuario)
            ->get(route('cuentas.index'))
            ->assertOk()
            ->assertViewIs('cuentas.index')
            ->assertSee('General')
            ->assertSee('Dinero de mi abuelita');
    }

    // -----------------------------------------------------------------------
    // 3-6. Crear cuentas
    // -----------------------------------------------------------------------

    public function test_crear_una_cuenta_personal_la_guarda_con_su_dueno(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->post(route('cuentas.store'), ['nombre' => 'Gasolina'])
            ->assertRedirect(route('cuentas.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('cuentas', [
            'nombre' => 'Gasolina',
            'user_id' => $usuario->id,
            'familia_id' => null,
        ]);
    }

    public function test_crear_una_cuenta_con_ambito_familia_la_guarda_en_el_hogar(): void
    {
        $usuario = $this->usuario();
        $familia = $this->hogar($usuario);

        $this->actingAs($usuario)
            ->post(route('cuentas.store'), ['nombre' => 'Gas', 'ambito' => 'familia'])
            ->assertRedirect(route('cuentas.index', ['ambito' => 'familia']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('cuentas', [
            'nombre' => 'Gas',
            'user_id' => null,
            'familia_id' => $familia->id,
        ]);
    }

    public function test_no_se_repite_el_nombre_en_el_mismo_ambito_pero_si_en_otro(): void
    {
        $usuario = $this->usuario();
        $familia = $this->hogar($usuario);

        // "Gas" personal.
        $this->actingAs($usuario)
            ->post(route('cuentas.store'), ['nombre' => 'Gas'])
            ->assertRedirect(route('cuentas.index'));

        // El mismo nombre en el hogar sí se permite: son espacios distintos.
        $this->actingAs($usuario)
            ->post(route('cuentas.store'), ['nombre' => 'Gas', 'ambito' => 'familia'])
            ->assertRedirect(route('cuentas.index', ['ambito' => 'familia']));

        // Repetirlo en el mismo ámbito falla, sin importar mayúsculas.
        $this->actingAs($usuario)
            ->from(route('cuentas.index'))
            ->post(route('cuentas.store'), ['nombre' => 'gas'])
            ->assertSessionHasErrors('nombre');

        $this->actingAs($usuario)
            ->from(route('cuentas.index', ['ambito' => 'familia']))
            ->post(route('cuentas.store'), ['nombre' => 'GAS', 'ambito' => 'familia'])
            ->assertSessionHasErrors('nombre');

        // 2 cuentas General (usuario y hogar) + 2 cuentas "Gas".
        $this->assertDatabaseCount('cuentas', 4);
    }

    public function test_el_nombre_es_obligatorio_y_tiene_tope_de_sesenta_caracteres(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->from(route('cuentas.index'))
            ->post(route('cuentas.store'), [])
            ->assertSessionHasErrors('nombre');

        $this->actingAs($usuario)
            ->from(route('cuentas.index'))
            ->post(route('cuentas.store'), ['nombre' => str_repeat('a', 61)])
            ->assertSessionHasErrors('nombre');

        // Solo queda la cuenta General automática.
        $this->assertDatabaseCount('cuentas', 1);
    }

    // -----------------------------------------------------------------------
    // 7-8. Renombrar
    // -----------------------------------------------------------------------

    public function test_renombrar_una_cuenta_propia_funciona(): void
    {
        $usuario = $this->usuario();
        $cuenta = $this->cuentaPersonal($usuario, 'Lonche');

        $this->actingAs($usuario)
            ->patch(route('cuentas.update', $cuenta), ['nombre' => 'Lonche semanal'])
            ->assertRedirect(route('cuentas.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuenta->id,
            'nombre' => 'Lonche semanal',
        ]);
    }

    public function test_no_se_puede_renombrar_la_cuenta_de_otro_usuario(): void
    {
        $ajeno = $this->usuario(['email' => 'ajeno@example.com']);
        $cuenta = $this->cuentaPersonal($ajeno, 'Cuenta ajena');

        $this->actingAs($this->usuario())
            ->patch(route('cuentas.update', $cuenta), ['nombre' => 'Intento de cambio'])
            ->assertNotFound();

        $this->assertDatabaseHas('cuentas', [
            'id' => $cuenta->id,
            'nombre' => 'Cuenta ajena',
        ]);
    }

    // -----------------------------------------------------------------------
    // 9-11. Eliminar
    // -----------------------------------------------------------------------

    public function test_borrar_una_cuenta_mueve_sus_gastos_a_la_general(): void
    {
        $usuario = $this->usuario();
        $general = $usuario->cuentaGeneral();
        $cuenta = $this->cuentaPersonal($usuario, 'Gas');

        $gasto = GastoHormiga::factory()->create([
            'user_id' => $usuario->id,
            'familia_id' => null,
            'cuenta_id' => $cuenta->id,
            'descripcion' => 'Tanque de gas',
        ]);

        $this->actingAs($usuario)
            ->delete(route('cuentas.destroy', $cuenta))
            ->assertRedirect(route('cuentas.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('cuentas', ['id' => $cuenta->id]);
        $this->assertDatabaseHas('gastos_hormiga', [
            'id' => $gasto->id,
            'cuenta_id' => $general->id,
        ]);
    }

    public function test_borrar_la_cuenta_general_esta_prohibido(): void
    {
        $usuario = $this->usuario();
        $general = $usuario->cuentaGeneral();

        $this->actingAs($usuario)
            ->from(route('cuentas.index'))
            ->delete(route('cuentas.destroy', $general))
            ->assertSessionHasErrors('cuenta');

        $this->assertDatabaseHas('cuentas', [
            'id' => $general->id,
            'nombre' => 'General',
            'user_id' => $usuario->id,
        ]);
    }

    public function test_no_se_puede_borrar_la_cuenta_de_otro_usuario(): void
    {
        $ajeno = $this->usuario(['email' => 'ajeno@example.com']);
        $cuenta = $this->cuentaPersonal($ajeno, 'Cuenta ajena');

        $this->actingAs($this->usuario())
            ->delete(route('cuentas.destroy', $cuenta))
            ->assertNotFound();

        $this->assertDatabaseHas('cuentas', ['id' => $cuenta->id]);
    }

    // -----------------------------------------------------------------------
    // 12. Aislamiento entre usuarios
    // -----------------------------------------------------------------------

    public function test_las_cuentas_que_ve_un_usuario_no_incluyen_las_de_otros(): void
    {
        $usuario = $this->usuario();
        $ajeno = $this->usuario(['name' => 'Otra Persona', 'email' => 'ajeno@example.com']);

        $this->cuentaPersonal($usuario, 'Mi alcancía');
        $this->cuentaPersonal($ajeno, 'Cuenta secreta');

        $familiaAjena = $this->hogar($ajeno, [
            'nombre' => 'Hogar ajeno',
            'codigo_invitacion' => 'AJENO123',
        ]);
        $this->cuentaDelHogar($familiaAjena, 'Gas ajeno');

        $this->actingAs($usuario)
            ->get(route('cuentas.index'))
            ->assertOk()
            ->assertSee('Mi alcancía')
            ->assertSee('General')
            ->assertDontSee('Cuenta secreta')
            ->assertDontSee('Gas ajeno');
    }
}
