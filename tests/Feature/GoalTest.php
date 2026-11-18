<?php

namespace Tests\Feature;

use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_list_update_delete(): void
    {
        $user = $this->apiUser();

        $id = $this->postJson('/api/goals', [
            'name' => 'Moto', 'icon' => '🚗', 'target' => 1000, 'saved' => 200, 'deadline' => '2027-05-01',
        ])->assertCreated()->assertJsonPath('data.saved', 200)->json('data.id');

        $this->assertDatabaseHas('goal_contributions', ['goal_id' => $id, 'comment' => 'Ahorro inicial']);

        $this->getJson('/api/goals')->assertOk()
            ->assertJsonPath('summary.saved', 200)
            ->assertJsonPath('summary.target', 1000)
            ->assertJsonPath('summary.count', 1);

        $this->putJson("/api/goals/{$id}", ['name' => 'Moto nueva', 'target' => 1500])
            ->assertOk()->assertJsonPath('data.name', 'Moto nueva');

        // El ahorro solo cambia con abonos/retiros
        $this->putJson("/api/goals/{$id}", ['name' => 'Moto', 'target' => 1500, 'saved' => 9999])
            ->assertStatus(422)->assertJsonValidationErrors(['saved']);

        $this->deleteJson("/api/goals/{$id}")->assertOk();
        $this->assertDatabaseMissing('goal_contributions', ['goal_id' => $id]); // cascada
        $this->assertSame(0, $user->goals()->count());
    }

    public function test_contributions_and_single_goal_reached_notification(): void
    {
        $user = $this->apiUser();
        $goal = Goal::factory()->for($user)->create(['target' => 1000, 'saved' => 0]);

        $this->postJson("/api/goals/{$goal->id}/contributions", ['type' => 'abonar', 'amount' => 600, 'comment' => 'quincena'])
            ->assertCreated()->assertJsonPath('data.saved', 600)->assertJsonPath('data.completed', false);

        $this->postJson("/api/goals/{$goal->id}/contributions", ['type' => 'abonar', 'amount' => 400])
            ->assertCreated()->assertJsonPath('data.completed', true);

        $this->postJson("/api/goals/{$goal->id}/contributions", ['type' => 'abonar', 'amount' => 10])->assertCreated();

        $reached = $user->notifications()->get()->filter(fn ($n) => $n->data['title'] === 'Meta alcanzada');
        $this->assertCount(1, $reached);

        // Retirar más de lo ahorrado → 400
        $this->postJson("/api/goals/{$goal->id}/contributions", ['type' => 'retirar', 'amount' => 5000])
            ->assertStatus(400)->assertJsonStructure(['message']);

        $this->postJson("/api/goals/{$goal->id}/contributions", ['type' => 'retirar', 'amount' => 510])
            ->assertCreated()->assertJsonPath('data.saved', 500);
        $this->assertFalse($goal->fresh()->completed_notified);

        $this->getJson("/api/goals/{$goal->id}/contributions")->assertOk()->assertJsonCount(4, 'data');
    }

    public function test_goal_notification_respects_preference(): void
    {
        $user = $this->apiUser();
        $user->settings->update(['notif_goals' => false]);
        $goal = Goal::factory()->for($user)->create(['target' => 100, 'saved' => 0]);

        $this->postJson("/api/goals/{$goal->id}/contributions", ['type' => 'abonar', 'amount' => 100])->assertCreated();
        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_cannot_touch_goals_of_others(): void
    {
        $goal = Goal::factory()->create();
        $this->apiUser();

        $this->getJson("/api/goals/{$goal->id}")->assertForbidden();
        $this->postJson("/api/goals/{$goal->id}/contributions", ['type' => 'abonar', 'amount' => 1])->assertForbidden();
        $this->deleteJson("/api/goals/{$goal->id}")->assertForbidden();
        $this->getJson('/api/goals/999999')->assertNotFound();
    }
}
