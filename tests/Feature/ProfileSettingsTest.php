<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\Movement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_profile_sanitizes_input(): void
    {
        $this->apiUser();

        $this->putJson('/api/profile', ['name' => '<script>x</script>Ana', 'phone' => '+57 311 000 0000', 'occupation' => 'Docente'])
            ->assertOk()->assertJsonPath('data.name', 'xAna')->assertJsonPath('data.occupation', 'Docente');

        $this->putJson('/api/profile', ['name' => '', 'phone' => 'abc'])->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone']);
    }

    public function test_avatar_upload_validation(): void
    {
        Storage::fake('public');
        $user = $this->apiUser();
        // PNG real de 1x1 px (no requiere la extensión GD)
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        $path = $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->createWithContent('foto.png', $png)], ['Accept' => 'application/json'])
            ->assertOk()->json('data.avatar');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['avatar']);

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->createWithContent('big.png', $png)->size(6000)], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_change_password(): void
    {
        $user = $this->apiUser(['password' => 'actual1']);

        $this->putJson('/api/profile/password', ['current_password' => 'mala', 'password' => 'nueva12', 'password_confirmation' => 'nueva12'])
            ->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        $this->putJson('/api/profile/password', ['current_password' => 'actual1', 'password' => 'nueva12', 'password_confirmation' => 'nueva12'])
            ->assertOk();
        $this->assertTrue(password_verify('nueva12', $user->fresh()->password));
    }

    public function test_settings_partial_update(): void
    {
        $this->apiUser();

        $this->getJson('/api/settings')->assertOk()->assertJsonPath('data.appearance.theme', 'light');
        $this->putJson('/api/settings', ['theme' => 'dark', 'notif_weekly' => true])->assertOk()
            ->assertJsonPath('data.appearance.theme', 'dark')
            ->assertJsonPath('data.notifications.notif_weekly', true)
            ->assertJsonPath('data.notifications.notif_email', true);
        $this->putJson('/api/settings', ['theme' => 'neon'])->assertStatus(422);
        $this->putJson('/api/settings', [])->assertStatus(422);
    }

    public function test_delete_account_cascades(): void
    {
        $this->seedCategories();
        $user = $this->apiUser();
        Movement::factory()->count(2)->for($user)->create();
        Goal::factory()->for($user)->create()->contributions()->create(['type' => 'abonar', 'amount' => 5]);

        $this->deleteJson('/api/profile')->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseCount('movements', 0);
        $this->assertDatabaseCount('goals', 0);
        $this->assertDatabaseCount('goal_contributions', 0);
        $this->assertDatabaseCount('user_settings', 0);
    }
}
