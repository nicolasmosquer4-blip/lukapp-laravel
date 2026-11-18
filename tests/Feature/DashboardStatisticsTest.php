<?php

namespace Tests\Feature;

use App\Models\Movement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCategories();
    }

    public function test_dashboard_aggregates_user_data(): void
    {
        $user = $this->apiUser();
        $today = Carbon::today()->toDateString();
        Movement::factory()->for($user)->create(['type' => 'ingreso', 'category_id' => $this->category('Salario', 'ingreso')->id, 'amount' => 1000, 'date' => $today, 'status' => 'completado']);
        Movement::factory()->for($user)->create(['type' => 'gasto', 'category_id' => $this->category('Salud')->id, 'amount' => 300, 'date' => $today, 'status' => 'completado']);
        Movement::factory()->for($user)->create(['type' => 'gasto', 'category_id' => $this->category('Salud')->id, 'amount' => 999, 'date' => $today, 'status' => 'cancelado']);
        Movement::factory()->create(['amount' => 777777]); // otro usuario

        $this->getJson('/api/dashboard')->assertOk()
            ->assertJsonPath('data.incomes', 1000)
            ->assertJsonPath('data.expenses', 300)
            ->assertJsonPath('data.balance', 700)
            ->assertJsonCount(3, 'data.recent')
            ->assertJsonCount(6, 'data.expensesByMonth');
    }

    public function test_statistics_periods(): void
    {
        $user = $this->apiUser();
        Movement::factory()->for($user)->create([
            'type' => 'gasto', 'category_id' => $this->category('Vivienda')->id, 'amount' => 600,
            'date' => Carbon::today()->toDateString(), 'status' => 'completado',
        ]);
        Movement::factory()->for($user)->create([
            'type' => 'ingreso', 'category_id' => $this->category('Salario', 'ingreso')->id, 'amount' => 1200,
            'date' => Carbon::today()->subYears(2)->toDateString(), 'status' => 'completado',
        ]);

        $this->getJson('/api/statistics?period=6M')->assertOk()
            ->assertJsonCount(6, 'data.monthlyData')
            ->assertJsonCount(7, 'data.dailyData')
            ->assertJsonPath('data.categoryBreakdown.0.category', 'Vivienda')
            ->assertJsonPath('data.categoryBreakdown.0.percentage', 100);

        $all = $this->getJson('/api/statistics?period=TODO')->assertOk()->json('data');
        $this->assertGreaterThanOrEqual(25, count($all['monthlyData']));
        $this->assertSame(1200, (int) $all['bestMonth']['income']);

        $this->getJson('/api/statistics?period=5Y')->assertStatus(422);
    }
}
