<?php

namespace Tests\Feature;

use App\Models\Familia;
use App\Models\GastoHormiga;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del hogar compartido: crearlo, unirse con el código de invitación,
 * salir, eliminarlo y regenerar el código.
 *
 * Reglas que se comprueban aquí:
 * - Cada persona pertenece a un solo hogar.
 * - El propietario es quien creó el hogar: solo él lo elimina y cambia el
 *   código, y él no puede "salir" (tiene que eliminarlo).
 * - Crear un hogar deja al creador como miembro y crea la cuenta "General"
 *   del hogar automáticamente.
 *
 * La base de datos de tests es SQLite en memoria (ver phpunit.xml), así que
 * RefreshDatabase migra el esquema desde cero en cada test.
 */
class FamiliaTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------------

    /** Crea un usuario de prueba. */
    private function usuario(array $atributos = []): User
    {
        return User::factory()->create($atributos);
    }

    /**
     * Crea un hogar con su propietario y, opcionalmente, más miembros.
     *
     * @param  list<User>  $miembros
     * @param  array<string, mixed>  $atributos
     */
    private function hogar(User $propietario, array $miembros = [], array $atributos = []): Familia
    {
        $familia = Familia::create(array_merge([
            'nombre' => 'Casa Solar',
            'propietario_id' => $propietario->id,
            'codigo_invitacion' => 'ABC12345',
        ], $atributos));

        $familia->miembros()->attach(array_merge(
            [$propietario->id],
            array_map(static fn (User $miembro): int => $miembro->id, $miembros),
        ));

        return $familia;
    }

    // -----------------------------------------------------------------------
    // 1-2. Acceso y pantalla sin hogar
    // -----------------------------------------------------------------------

    public function test_un_invitado_no_puede_ver_el_hogar(): void
    {
        $this->get(route('familia.index'))->assertRedirect(route('login'));
    }

    public function test_sin_hogar_la_pantalla_muestra_la_invitacion_a_crear_o_unirse(): void
    {
        $this->actingAs($this->usuario())
            ->get(route('familia.index'))
            ->assertOk()
            ->assertViewIs('familia.index')
            ->assertViewHas('familia', null)
            ->assertSee('Crear un hogar')
            ->assertSee('Unirme con un código');
    }

    // -----------------------------------------------------------------------
    // 3-5. Crear el hogar
    // -----------------------------------------------------------------------

    public function test_crear_un_hogar_guarda_la_familia_la_membresia_y_su_cuenta_general(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->post(route('familia.store'), ['nombre' => 'Casa Solar'])
            ->assertRedirect(route('familia.index'))
            ->assertSessionHas('status');

        $familia = Familia::query()->where('nombre', 'Casa Solar')->firstOrFail();

        $this->assertSame($usuario->id, $familia->propietario_id);

        $this->assertDatabaseHas('familias', [
            'id' => $familia->id,
            'nombre' => 'Casa Solar',
            'propietario_id' => $usuario->id,
        ]);
        $this->assertDatabaseHas('familia_user', [
            'familia_id' => $familia->id,
            'user_id' => $usuario->id,
        ]);
        $this->assertDatabaseHas('cuentas', [
            'nombre' => 'General',
            'familia_id' => $familia->id,
            'user_id' => null,
        ]);
    }

    public function test_crear_un_hogar_cuando_ya_se_tiene_uno_no_lo_duplica(): void
    {
        $usuario = $this->usuario();
        $familia = $this->hogar($usuario);

        $this->actingAs($usuario)
            ->post(route('familia.store'), ['nombre' => 'Otro hogar'])
            ->assertRedirect(route('familia.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('familias', 1);
        $this->assertDatabaseHas('familias', [
            'id' => $familia->id,
            'nombre' => 'Casa Solar',
        ]);
        $this->assertDatabaseCount('familia_user', 1);
    }

    public function test_el_nombre_del_hogar_es_obligatorio(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->from(route('familia.index'))
            ->post(route('familia.store'), [])
            ->assertSessionHasErrors('nombre');

        $this->assertDatabaseCount('familias', 0);
    }

    // -----------------------------------------------------------------------
    // 6-8. Unirse con código
    // -----------------------------------------------------------------------

    public function test_unirse_con_un_codigo_valido_agrega_al_usuario_como_miembro(): void
    {
        $propietario = $this->usuario(['email' => 'dueno@example.com']);
        $familia = $this->hogar($propietario);
        $nuevo = $this->usuario(['email' => 'nuevo@example.com']);

        // El código se guarda en mayúsculas; la petición llega en minúsculas.
        $this->actingAs($nuevo)
            ->post(route('familia.unirse'), ['codigo' => 'abc12345'])
            ->assertRedirect(route('familia.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('familia_user', [
            'familia_id' => $familia->id,
            'user_id' => $nuevo->id,
        ]);
    }

    public function test_unirse_con_un_codigo_inexistente_da_error_y_no_crea_membresia(): void
    {
        $usuario = $this->usuario();

        $this->actingAs($usuario)
            ->from(route('familia.index'))
            ->post(route('familia.unirse'), ['codigo' => 'NOEXISTE'])
            ->assertSessionHasErrors('codigo');

        $this->assertDatabaseCount('familias', 0);
        $this->assertDatabaseMissing('familia_user', ['user_id' => $usuario->id]);
    }

    public function test_unirse_cuando_ya_se_tiene_hogar_redirige_con_error_y_no_cambia_la_membresia(): void
    {
        $usuario = $this->usuario();
        $propio = $this->hogar($usuario);

        $ajeno = $this->hogar(
            $this->usuario(['email' => 'ajeno@example.com']),
            [],
            ['nombre' => 'Otro hogar', 'codigo_invitacion' => 'ZZZ99999'],
        );

        $this->actingAs($usuario)
            ->post(route('familia.unirse'), ['codigo' => 'ZZZ99999'])
            ->assertRedirect(route('familia.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('familia_user', [
            'familia_id' => $propio->id,
            'user_id' => $usuario->id,
        ]);
        $this->assertDatabaseMissing('familia_user', [
            'familia_id' => $ajeno->id,
            'user_id' => $usuario->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // 9-10. Salir del hogar
    // -----------------------------------------------------------------------

    public function test_un_miembro_puede_salir_del_hogar(): void
    {
        $propietario = $this->usuario(['email' => 'dueno@example.com']);
        $miembro = $this->usuario(['email' => 'miembro@example.com']);
        $familia = $this->hogar($propietario, [$miembro]);

        $this->actingAs($miembro)
            ->delete(route('familia.salir'))
            ->assertRedirect(route('familia.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('familia_user', ['user_id' => $miembro->id]);
        $this->assertDatabaseHas('familias', ['id' => $familia->id]);
        $this->assertDatabaseHas('familia_user', [
            'familia_id' => $familia->id,
            'user_id' => $propietario->id,
        ]);
    }

    public function test_el_propietario_no_puede_salir_del_hogar(): void
    {
        $propietario = $this->usuario();
        $familia = $this->hogar($propietario);

        $this->actingAs($propietario)
            ->delete(route('familia.salir'))
            ->assertRedirect(route('familia.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('familia_user', [
            'familia_id' => $familia->id,
            'user_id' => $propietario->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // 11-12. Eliminar el hogar
    // -----------------------------------------------------------------------

    public function test_el_propietario_puede_eliminar_el_hogar_y_se_borra_en_cascada(): void
    {
        $propietario = $this->usuario(['email' => 'dueno@example.com']);
        $miembro = $this->usuario(['email' => 'miembro@example.com']);
        $familia = $this->hogar($propietario, [$miembro]);

        $cuenta = $familia->cuentaGeneral();
        GastoHormiga::factory()->create([
            'user_id' => $miembro->id,
            'familia_id' => $familia->id,
            'cuenta_id' => $cuenta->id,
        ]);

        $this->actingAs($propietario)
            ->delete(route('familia.destroy'))
            ->assertRedirect(route('familia.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('familias', ['id' => $familia->id]);
        $this->assertDatabaseMissing('familia_user', ['familia_id' => $familia->id]);
        $this->assertDatabaseMissing('cuentas', ['familia_id' => $familia->id]);
        $this->assertDatabaseMissing('gastos_hormiga', ['familia_id' => $familia->id]);
    }

    public function test_un_miembro_no_propietario_no_puede_eliminar_el_hogar(): void
    {
        $propietario = $this->usuario(['email' => 'dueno@example.com']);
        $miembro = $this->usuario(['email' => 'miembro@example.com']);
        $familia = $this->hogar($propietario, [$miembro]);

        $this->actingAs($miembro)
            ->delete(route('familia.destroy'))
            ->assertForbidden();

        $this->assertDatabaseHas('familias', ['id' => $familia->id]);
        $this->assertDatabaseHas('familia_user', [
            'familia_id' => $familia->id,
            'user_id' => $miembro->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // 13-14. Código de invitación
    // -----------------------------------------------------------------------

    public function test_el_propietario_puede_regenerar_el_codigo_de_invitacion(): void
    {
        $propietario = $this->usuario();
        $familia = $this->hogar($propietario);

        $this->actingAs($propietario)
            ->post(route('familia.codigo'))
            ->assertRedirect(route('familia.index'))
            ->assertSessionHas('status');

        $familia->refresh();

        $this->assertNotSame('ABC12345', $familia->codigo_invitacion);
        $this->assertDatabaseMissing('familias', ['codigo_invitacion' => 'ABC12345']);
        $this->assertDatabaseHas('familias', [
            'id' => $familia->id,
            'codigo_invitacion' => $familia->codigo_invitacion,
        ]);
    }

    public function test_un_miembro_no_propietario_no_puede_regenerar_el_codigo(): void
    {
        $propietario = $this->usuario(['email' => 'dueno@example.com']);
        $miembro = $this->usuario(['email' => 'miembro@example.com']);
        $familia = $this->hogar($propietario, [$miembro]);

        $this->actingAs($miembro)
            ->post(route('familia.codigo'))
            ->assertForbidden();

        $this->assertDatabaseHas('familias', [
            'id' => $familia->id,
            'codigo_invitacion' => 'ABC12345',
        ]);
    }
}
