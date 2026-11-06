<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Pruebas del registro, el inicio de sesión y el cierre de sesión.
 *
 * La app es multiusuario: sin sesión no se llega a los gastos. La base de
 * datos de tests es SQLite en memoria (ver phpunit.xml), así que
 * RefreshDatabase migra el esquema desde cero en cada test.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Utilidades
    // -----------------------------------------------------------------------

    /** Crea un usuario con credenciales conocidas. */
    private function usuario(array $atributos = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => 'password',
        ], $atributos));
    }

    /** Payload válido para el registro, con campos sobrescribibles. */
    private function datosDeRegistro(array $sobrescribir = []): array
    {
        return array_merge([
            'name' => 'Diego Ramírez',
            'email' => 'diego@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ], $sobrescribir);
    }

    // -----------------------------------------------------------------------
    // 1-3. Acceso y pantallas públicas
    // -----------------------------------------------------------------------

    public function test_un_invitado_que_entra_a_gastos_es_redirigido_al_login(): void
    {
        $this->get(route('gastos.index'))->assertRedirect(route('login'));
    }

    public function test_la_pantalla_de_login_se_renderiza(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    public function test_la_pantalla_de_registro_se_renderiza(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertViewIs('auth.register');
    }

    // -----------------------------------------------------------------------
    // 4-5. Inicio de sesión
    // -----------------------------------------------------------------------

    public function test_un_usuario_puede_entrar_con_sus_credenciales(): void
    {
        $user = $this->usuario();

        $this->post(route('login'), [
            'email' => 'ana@example.com',
            'password' => 'password',
        ])->assertRedirect(route('gastos.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_un_usuario_no_entra_con_la_contrasena_incorrecta(): void
    {
        $this->usuario();

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'ana@example.com',
                'password' => 'contraseña-equivocada',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Tras cinco intentos fallidos, el sexto avisa que hay que esperar.
     * El límite es por correo + IP, así que se usa el mismo correo siempre.
     */
    public function test_demasiados_intentos_de_login_bloquean_temporalmente(): void
    {
        $this->usuario();
        RateLimiter::clear('ana@example.com|127.0.0.1');

        $credenciales = [
            'email' => 'ana@example.com',
            'password' => 'no-es-la-buena',
        ];

        for ($intento = 1; $intento <= 5; $intento++) {
            $this->from(route('login'))
                ->post(route('login'), $credenciales)
                ->assertSessionHasErrors('email');
        }

        $respuesta = $this->from(route('login'))->post(route('login'), $credenciales);

        $respuesta->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Demasiados intentos',
            (string) session('errors')->first('email'),
            'El sexto intento debe avisar del bloqueo temporal.'
        );

        $this->assertGuest();
        RateLimiter::clear('ana@example.com|127.0.0.1');
    }

    // -----------------------------------------------------------------------
    // 6-8. Registro
    // -----------------------------------------------------------------------

    public function test_el_registro_crea_al_usuario_y_lo_deja_autenticado(): void
    {
        $respuesta = $this->post(route('register'), $this->datosDeRegistro());

        $respuesta->assertRedirect(route('gastos.index'));
        $respuesta->assertSessionHas('status');

        $user = User::where('email', 'diego@example.com')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'name' => 'Diego Ramírez',
            'email' => 'diego@example.com',
        ]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_el_registro_valida_los_campos(): void
    {
        // 1) Campos obligatorios.
        $this->from(route('register'))
            ->post(route('register'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        // 2) Correo ya registrado.
        $this->usuario(['email' => 'ocupado@example.com']);

        $this->from(route('register'))
            ->post(route('register'), $this->datosDeRegistro([
                'email' => 'ocupado@example.com',
            ]))
            ->assertSessionHasErrors('email');

        // 3) Contraseña demasiado corta (mínimo 8 caracteres).
        $this->from(route('register'))
            ->post(route('register'), $this->datosDeRegistro([
                'email' => 'corta@example.com',
                'password' => 'corta',
                'password_confirmation' => 'corta',
            ]))
            ->assertSessionHasErrors('password');

        // 4) La confirmación no coincide.
        $this->from(route('register'))
            ->post(route('register'), $this->datosDeRegistro([
                'email' => 'desmatch@example.com',
                'password' => 'secreto123',
                'password_confirmation' => 'secreto124',
            ]))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'corta@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'desmatch@example.com']);
    }

    public function test_el_registro_guarda_la_contrasena_hasheada(): void
    {
        $this->post(route('register'), $this->datosDeRegistro([
            'password' => 'mi-clave-segura',
            'password_confirmation' => 'mi-clave-segura',
        ]));

        $user = User::where('email', 'diego@example.com')->firstOrFail();

        $this->assertNotSame('mi-clave-segura', $user->password);
        $this->assertStringStartsWith('$2y$', $user->password);
        $this->assertTrue(Hash::check('mi-clave-segura', $user->password));
        $this->assertDatabaseMissing('users', ['password' => 'mi-clave-segura']);
    }

    // -----------------------------------------------------------------------
    // 9. Cierre de sesión
    // -----------------------------------------------------------------------

    public function test_cerrar_sesion_cierra_la_sesion_y_redirige_al_login(): void
    {
        $user = $this->usuario();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // -----------------------------------------------------------------------
    // 10-12. Redirecciones de invitados y usuarios con sesión
    // -----------------------------------------------------------------------

    public function test_un_usuario_autenticado_que_visita_login_termina_en_gastos(): void
    {
        $user = $this->usuario();

        $respuesta = $this->actingAs($user)->get(route('login'));

        $respuesta->assertRedirect();

        // El middleware "guest" puede mandar directo a gastos.index o pasar
        // por "/" (que a su vez redirige a gastos.index). Se comprueba el
        // destino final para no depender del salto intermedio.
        if ($respuesta->headers->get('Location') !== route('gastos.index')) {
            $ruta = parse_url((string) $respuesta->headers->get('Location'), PHP_URL_PATH) ?: '/';

            $this->get($ruta)->assertRedirect(route('gastos.index'));
        }

        $this->actingAs($user)
            ->followingRedirects()
            ->get(route('login'))
            ->assertOk()
            ->assertViewIs('gastos.index');
    }

    public function test_la_raiz_redirige_a_gastos_cuando_hay_sesion(): void
    {
        $this->actingAs($this->usuario())
            ->get('/')
            ->assertRedirect(route('gastos.index'));
    }

    public function test_un_invitado_que_visita_la_raiz_es_redirigido_al_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
