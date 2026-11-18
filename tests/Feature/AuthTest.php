<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_settings_and_returns_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Laura Pérez',
            'email' => 'LAURA@example.com',
            'phone' => '+57 300 123 4567',
            'password' => 'secreto1',
            'password_confirmation' => 'secreto1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'laura@example.com')
            ->assertJsonPath('data.role', 'user')
            ->assertJsonStructure(['message', 'data' => ['id', 'name', 'email'], 'token', 'redirect']);

        $user = User::where('email', 'laura@example.com')->first();
        $this->assertNotNull($user->settings);
        $this->assertNotSame('secreto1', $user->password, 'La contraseña debe guardarse con hash');
        $this->assertTrue(password_verify('secreto1', $user->password));
        $this->assertSame(1, $user->notifications()->count()); // bienvenida
    }

    public function test_register_validates_input(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'dup@example.com',
            'password' => '123',
            'password_confirmation' => '456',
        ])->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_cannot_escalate_role(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Intruso', 'email' => 'x@example.com', 'role' => 'admin',
            'password' => 'secreto1', 'password_confirmation' => 'secreto1',
        ])->assertCreated()->assertJsonPath('data.role', 'user');
    }

    public function test_login_success_wrong_password_and_blocked(): void
    {
        $user = User::factory()->create(['email' => 'ok@example.com', 'password' => 'secreto1']);
        User::factory()->blocked()->create(['email' => 'blocked@example.com', 'password' => 'secreto1']);

        $this->postJson('/api/auth/login', ['email' => 'ok@example.com', 'password' => 'secreto1'])
            ->assertOk()->assertJsonStructure(['token', 'data' => ['id']]);
        $this->assertNotNull($user->fresh()->last_access_at);

        $this->postJson('/api/auth/login', ['email' => 'ok@example.com', 'password' => 'otra'])
            ->assertStatus(401)->assertJson(['message' => 'Correo o contraseña incorrectos.']);

        $this->postJson('/api/auth/login', ['email' => 'blocked@example.com', 'password' => 'secreto1'])
            ->assertStatus(403);
    }

    public function test_protected_endpoints_require_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401)->assertJsonStructure(['message']);
        $this->getJson('/api/movements')->assertStatus(401);
    }

    public function test_token_logout_revokes_token(): void
    {
        $user = User::factory()->create(['password' => 'secreto1']);
        $token = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'secreto1'])->json('token');

        $this->withToken($token)->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.id', $user->id);
        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['email' => 'nadie@example.com', 'password' => 'x']);
        }
        $this->postJson('/api/auth/login', ['email' => 'nadie@example.com', 'password' => 'x'])->assertStatus(429);
    }
}
