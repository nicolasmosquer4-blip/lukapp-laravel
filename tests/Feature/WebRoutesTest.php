<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_pages_and_protection(): void
    {
        $this->get('/login')->assertOk()->assertSee('Iniciar sesión')->assertSee('csrf-token', false);
        $this->get('/registro')->assertOk();
        $this->get('/olvide-contrasena')->assertOk();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
        $this->get('/dashboard.html')->assertRedirect('/dashboard');
    }

    public function test_user_navigation_and_role_guards(): void
    {
        $user = User::factory()->create(['name' => 'Camila Torres']);
        $this->actingAs($user);

        foreach (['/dashboard', '/movimientos', '/estadisticas', '/metas', '/configuracion'] as $page) {
            $this->get($page)->assertOk()->assertSee('Camila Torres');
        }
        $this->get('/admin')->assertRedirect('/dashboard');
        $this->get('/login')->assertRedirect('/dashboard');
    }

    public function test_admin_is_redirected_to_admin_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get('/dashboard')->assertRedirect('/admin');
        $this->get('/admin')->assertOk()->assertSee('Modo Administrador');
    }

    public function test_blocked_user_session_is_closed(): void
    {
        $this->actingAs(User::factory()->blocked()->create());
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }
}
