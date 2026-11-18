<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MovementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCategories();
    }

    public function test_full_crud_cycle(): void
    {
        $user = $this->apiUser();

        $id = $this->postJson('/api/movements', [
            'type' => 'gasto', 'category' => 'Alimentación', 'description' => 'Mercado',
            'amount' => 120000, 'date' => Carbon::today()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.category', 'Alimentación')
            ->assertJsonPath('data.status', 'completado')
            ->json('data.id');

        $this->getJson("/api/movements/{$id}")->assertOk()->assertJsonPath('data.amount', 120000);

        $this->putJson("/api/movements/{$id}", [
            'type' => 'gasto', 'category_id' => $this->category('Transporte')->id, 'description' => 'Taxi',
            'amount' => 30000, 'date' => Carbon::today()->toDateString(),
        ])->assertOk()->assertJsonPath('data.description', 'Taxi')->assertJsonPath('data.category', 'Transporte');

        $this->deleteJson("/api/movements/{$id}")->assertOk();
        $this->assertDatabaseMissing('movements', ['id' => $id]);
        $this->getJson("/api/movements/{$id}")->assertNotFound();

        $this->assertSame(1, $user->notifications()->count()); // "Nuevo gasto"
    }

    public function test_future_date_is_pending_and_excluded_from_totals(): void
    {
        $this->apiUser();

        $this->postJson('/api/movements', [
            'type' => 'ingreso', 'category' => 'Salario', 'description' => 'Pago futuro',
            'amount' => 1000, 'date' => Carbon::today()->addDays(5)->toDateString(),
        ])->assertCreated()->assertJsonPath('data.status', 'pendiente');

        $this->getJson('/api/movements')->assertOk()
            ->assertJsonPath('totals.income', 0)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_validation_errors(): void
    {
        $this->apiUser();

        $this->postJson('/api/movements', ['type' => 'otro', 'amount' => -5, 'date' => '18/11/2026'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'amount', 'date', 'description']);

        // Categoría de ingreso usada en un gasto
        $this->postJson('/api/movements', [
            'type' => 'gasto', 'category' => 'Salario', 'description' => 'X', 'amount' => 10, 'date' => '2026-01-01',
        ])->assertStatus(422)->assertJsonValidationErrors(['category']);
    }

    public function test_users_cannot_access_movements_of_others(): void
    {
        $other = Movement::factory()->create();
        $this->apiUser();

        $this->getJson("/api/movements/{$other->id}")->assertForbidden();
        $this->putJson("/api/movements/{$other->id}", [
            'type' => 'gasto', 'category' => 'Salud', 'description' => 'hack', 'amount' => 1, 'date' => '2026-01-01',
        ])->assertForbidden();
        $this->deleteJson("/api/movements/{$other->id}")->assertForbidden();

        // El borrado masivo ignora IDs ajenos
        $this->deleteJson('/api/movements', ['ids' => [$other->id]])->assertNotFound();
        $this->assertDatabaseHas('movements', ['id' => $other->id]);
    }

    public function test_filters_search_pagination_and_totals(): void
    {
        $user = $this->apiUser();
        $food = $this->category('Alimentación');
        $salary = $this->category('Salario', 'ingreso');

        Movement::factory()->count(10)->for($user)->create([
            'type' => 'gasto', 'category_id' => $food->id, 'amount' => 100, 'date' => '2026-01-10', 'status' => 'completado',
        ]);
        Movement::factory()->for($user)->create([
            'type' => 'ingreso', 'category_id' => $salary->id, 'amount' => 5000,
            'description' => 'Salario enero', 'date' => '2026-01-25', 'status' => 'completado',
        ]);
        Movement::factory()->count(3)->create(); // de otro usuario

        $this->getJson('/api/movements?per_page=8')->assertOk()
            ->assertJsonCount(8, 'data')
            ->assertJsonPath('meta.total', 11)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('totals.income', 5000)
            ->assertJsonPath('totals.expense', 1000);

        $this->getJson('/api/movements?type=ingreso')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/movements?q=salario')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/movements?q=alimentaci')->assertJsonPath('meta.total', 10); // por categoría
        $this->getJson('/api/movements?type=invalido')->assertStatus(422);
    }

    public function test_bulk_delete_and_csv_export(): void
    {
        $user = $this->apiUser();
        $ids = Movement::factory()->count(3)->for($user)->create()->pluck('id')->all();

        $csv = $this->get('/api/movements/export');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('content-type'));
        $this->assertStringContainsString('Descripcion,Tipo,Categoria,Fecha,Monto,Estado', $csv->streamedContent());

        $this->deleteJson('/api/movements', ['ids' => $ids])->assertOk()->assertJsonPath('deleted', 3);
        $this->assertSame(0, $user->movements()->count());
    }

    public function test_unusual_spending_alert_respects_preference(): void
    {
        $user = $this->apiUser();
        $food = $this->category('Alimentación');
        $monthStart = Carbon::today()->startOfMonth();
        foreach ([1, 2, 3] as $m) {
            Movement::factory()->for($user)->create([
                'type' => 'gasto', 'category_id' => $food->id, 'amount' => 100000,
                'date' => $monthStart->copy()->subMonths($m)->toDateString(), 'status' => 'completado',
            ]);
        }

        $payload = ['type' => 'gasto', 'category' => 'Alimentación', 'description' => 'Fiesta', 'amount' => 400000, 'date' => Carbon::today()->toDateString()];
        $this->postJson('/api/movements', $payload)->assertCreated();
        $this->postJson('/api/movements', $payload)->assertCreated();

        $alerts = $user->notifications()->get()->filter(fn ($n) => ($n->data['title'] ?? '') === 'Alerta de gasto');
        $this->assertCount(1, $alerts, 'Solo una alerta por categoría y mes');
    }
}
