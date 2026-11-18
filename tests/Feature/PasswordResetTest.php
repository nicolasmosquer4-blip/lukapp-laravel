<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_link_without_revealing_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();
        $this->postJson('/api/auth/forgot-password', ['email' => 'noexiste@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $url = $notification->toMail($user)->actionUrl;

            return str_contains($url, '/restablecer-contrasena/');
        });
    }

    public function test_reset_password_with_valid_and_invalid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => 'invalido', 'email' => $user->email,
            'password' => 'nueva123', 'password_confirmation' => 'nueva123',
        ])->assertStatus(400);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'nueva123', 'password_confirmation' => 'nueva123',
        ])->assertOk();

        $this->assertTrue(password_verify('nueva123', $user->fresh()->password));
    }
}
