<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_mark_read_and_isolation(): void
    {
        $user = $this->apiUser();
        $user->notify(new AppNotification('Uno', 'm1'));
        $user->notify(new AppNotification('Dos', 'm2'));
        $other = User::factory()->create();
        $other->notify(new AppNotification('Ajena', 'x'));

        $res = $this->getJson('/api/notifications')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('unread_count', 2);
        $id = $res->json('data.0.id');

        $this->putJson("/api/notifications/{$id}/read")->assertOk()->assertJsonPath('unread_count', 1);
        $this->putJson('/api/notifications/read-all')->assertOk();
        $this->assertSame(0, $user->unreadNotifications()->count());

        $foreign = $other->notifications()->first()->id;
        $this->putJson("/api/notifications/{$foreign}/read")->assertNotFound();
        $this->deleteJson("/api/notifications/{$foreign}")->assertNotFound();

        $this->deleteJson('/api/notifications')->assertOk();
        $this->assertSame(0, $user->notifications()->count());
        $this->assertSame(1, $other->notifications()->count());
    }
}
