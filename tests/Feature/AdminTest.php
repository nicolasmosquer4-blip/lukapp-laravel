<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCategories();
    }

    public function test_regular_users_cannot_use_admin_api(): void
    {
        $this->apiUser();
        $this->getJson('/api/admin/kpis')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_admins_cannot_use_user_finance_api(): void
    {
        $this->apiAdmin();
        $this->getJson('/api/movements')->assertForbidden();
    }

    public function test_kpis_users_search_and_details(): void
    {
        User::factory()->create(['name' => 'María López', 'email' => 'maria@example.com']);
        $target = User::factory()->create(['name' => 'Pedro Ruiz']);
        Movement::factory()->count(2)->for($target)->create();
        $this->apiAdmin();

        $this->getJson('/api/admin/kpis')->assertOk()
            ->assertJsonPath('data.totalUsers', 2)
            ->assertJsonPath('data.totalMovements', 2);

        $this->getJson('/api/admin/users?q=maria')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'maria@example.com');

        $this->getJson("/api/admin/users/{$target->id}")->assertOk()
            ->assertJsonPath('data.movementsCount', 2);

        $this->getJson('/api/admin/stats')->assertOk()->assertJsonCount(6, 'data.usersByMonth');
        $this->getJson('/api/admin/system')->assertOk()->assertJsonPath('data.database.status', 'ok');
    }

    public function test_block_user_revokes_access_immediately(): void
    {
        $target = User::factory()->create(['password' => 'secreto1']);
        $token = $target->createToken('test')->plainTextToken;

        $this->apiAdmin();
        $this->putJson("/api/admin/users/{$target->id}/block", ['blocked' => true])->assertOk()
            ->assertJsonPath('data.blocked', true);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401); // token revocado
        $this->postJson('/api/auth/login', ['email' => $target->email, 'password' => 'secreto1'])->assertForbidden();
    }

    public function test_blocked_user_with_active_session_is_rejected(): void
    {
        $user = User::factory()->blocked()->create();
        Sanctum::actingAs($user);
        $this->getJson('/api/dashboard')->assertForbidden();
    }

    public function test_admin_accounts_are_protected(): void
    {
        $otherAdmin = User::factory()->admin()->create();
        $this->apiAdmin();

        $this->putJson("/api/admin/users/{$otherAdmin->id}/block", ['blocked' => true])->assertForbidden();
        $this->deleteJson("/api/admin/users/{$otherAdmin->id}")->assertForbidden();
        $this->deleteJson("/api/admin/users/{$otherAdmin->id}/data")->assertForbidden();
    }

    public function test_reset_data_and_delete_user(): void
    {
        $target = User::factory()->create();
        Movement::factory()->count(3)->for($target)->create();
        $this->apiAdmin();

        $this->deleteJson("/api/admin/users/{$target->id}/data")->assertOk();
        $this->assertSame(0, $target->movements()->count());
        $this->assertDatabaseHas('users', ['id' => $target->id]);

        $this->deleteJson("/api/admin/users/{$target->id}")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->deleteJson("/api/admin/users/{$target->id}")->assertNotFound();
    }

    public function test_demo_mode_seeds_new_registrations(): void
    {
        $this->apiAdmin();
        $this->putJson('/api/admin/system/demo-mode', ['enabled' => true])->assertOk();
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/auth/register', [
            'name' => 'Demo', 'email' => 'demo@example.com', 'password' => 'secreto1', 'password_confirmation' => 'secreto1',
        ])->assertCreated();

        $user = User::where('email', 'demo@example.com')->first();
        $this->assertGreaterThan(0, $user->movements()->count());
        $this->assertSame(3, $user->goals()->count());
    }
}
