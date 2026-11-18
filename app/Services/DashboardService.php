<?php

namespace App\Services;

use App\Http\Resources\GoalResource;
use App\Http\Resources\MovementResource;
use App\Models\Movement;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Port de dashboardModel.js: consolida KPIs, movimientos recientes, metas y tendencia. */
class DashboardService
{
    public function build(User $user): array
    {
        Movement::syncStatusesForUser($user->id);

        // Totales en una sola consulta agregada (se ignoran los cancelados, como en el original)
        $totals = $user->movements()->notCancelled()
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'ingreso' THEN amount ELSE 0 END), 0) AS incomes")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'gasto' THEN amount ELSE 0 END), 0) AS expenses")
            ->first();

        $incomes = (float) $totals->incomes;
        $expenses = (float) $totals->expenses;
        $savings = $incomes - $expenses;

        $recent = $user->movements()->with('category')
            ->orderByDesc('date')->orderByDesc('id')
            ->limit(5)->get();

        $goals = $user->goals()->orderBy('created_at')->get();

        return [
            'incomes' => $incomes,
            'expenses' => $expenses,
            'savings' => $savings,
            'balance' => $savings,
            'savingsRate' => $incomes > 0 ? round(($savings / $incomes) * 100, 1) : 0,
            'recent' => MovementResource::collection($recent)->resolve(),
            'goals' => GoalResource::collection($goals)->resolve(),
            'expensesByMonth' => $this->monthlyExpenses($user),
        ];
    }

    /** Gastos de los últimos 6 meses (incluido el actual). */
    private function monthlyExpenses(User $user): array
    {
        $start = Carbon::today()->startOfMonth()->subMonths(5);
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $date = $start->copy()->addMonths($i);
            $months[$date->format('Y-m')] = [
                'key' => $date->format('Y-m'),
                'label' => rtrim($date->locale('es')->translatedFormat('M'), '.'),
                'value' => 0.0,
            ];
        }

        $user->movements()->notCancelled()
            ->where('type', Movement::TYPE_EXPENSE)
            ->whereDate('date', '>=', $start->toDateString())
            ->get(['date', 'amount'])
            ->each(function (Movement $m) use (&$months) {
                $key = $m->date->format('Y-m');
                if (isset($months[$key])) {
                    $months[$key]['value'] += (float) $m->amount;
                }
            });

        return array_values($months);
    }
}
