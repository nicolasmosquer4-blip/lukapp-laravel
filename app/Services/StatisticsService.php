<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\User;
use Illuminate\Support\Carbon;

/** Port de estadisticaModel.js: KPIs, serie mensual, desglose por categoría y últimos 7 días. */
class StatisticsService
{
    public const PERIODS = ['6M', '1A', 'TODO'];

    public function build(User $user, string $period = '6M'): array
    {
        Movement::syncStatusesForUser($user->id);

        $base = fn () => $user->movements()->notCancelled();
        $today = Carbon::today();
        $endOfMonth = $today->copy()->endOfMonth();

        $startDate = match ($period) {
            '1A' => $today->copy()->startOfMonth()->subMonths(11),
            'TODO' => ($oldest = $base()->min('date'))
                ? Carbon::parse($oldest)->startOfMonth()
                : $today->copy()->startOfMonth()->subMonths(5),
            default => $today->copy()->startOfMonth()->subMonths(5),
        };

        $monthsToAnalyze = max(1, ($today->year - $startDate->year) * 12 + ($today->month - $startDate->month) + 1);
        $showYear = $monthsToAnalyze > 12;

        // Solo las columnas necesarias, filtradas por índice (user_id, date)
        $scoped = $base()
            ->whereBetween('date', [$startDate->toDateString(), $endOfMonth->toDateString()])
            ->get(['type', 'amount', 'date', 'category_id']);

        $totalIncome = (float) $scoped->where('type', Movement::TYPE_INCOME)->sum('amount');
        $totalExpense = (float) $scoped->where('type', Movement::TYPE_EXPENSE)->sum('amount');

        // Serie mensual
        $monthly = [];
        for ($i = 0; $i < $monthsToAnalyze; $i++) {
            $d = $startDate->copy()->addMonths($i);
            $label = ucfirst(rtrim($d->locale('es')->translatedFormat('M'), '.'));
            if ($showYear) {
                $label .= ' '.$d->format('y');
            }
            $monthly[$d->format('Y-m')] = ['key' => $d->format('Y-m'), 'label' => $label, 'income' => 0.0, 'expense' => 0.0];
        }
        foreach ($scoped as $m) {
            $key = $m->date->format('Y-m');
            if (! isset($monthly[$key])) {
                continue;
            }
            $field = $m->type === Movement::TYPE_INCOME ? 'income' : 'expense';
            $monthly[$key][$field] += (float) $m->amount;
        }
        $monthly = array_values($monthly);

        $bestMonth = ['label' => '--', 'income' => 0.0];
        foreach ($monthly as $m) {
            if ($m['income'] > $bestMonth['income']) {
                $bestMonth = ['label' => $m['label'], 'income' => $m['income']];
            }
        }

        // Desglose de gastos por categoría (agregado en SQL)
        $breakdown = $base()
            ->join('categories', 'categories.id', '=', 'movements.category_id')
            ->where('movements.type', Movement::TYPE_EXPENSE)
            ->whereBetween('movements.date', [$startDate->toDateString(), $endOfMonth->toDateString()])
            ->groupBy('categories.name', 'categories.color')
            ->selectRaw('categories.name AS category, categories.color AS color, SUM(movements.amount) AS total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'total' => (float) $row->total,
                'percentage' => $totalExpense > 0 ? round(((float) $row->total / $totalExpense) * 100, 2) : 0,
                'color' => $row->color,
            ])->values()->all();

        // Gastos de los últimos 7 días (independiente del filtro de periodo)
        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $daily[$d->toDateString()] = [
                'dateStr' => $d->toDateString(),
                'label' => ucfirst(str_replace('.', '', $d->locale('es')->translatedFormat('D'))),
                'expense' => 0.0,
            ];
        }
        $base()->where('type', Movement::TYPE_EXPENSE)
            ->whereBetween('date', [$today->copy()->subDays(6)->toDateString(), $today->toDateString()])
            ->get(['date', 'amount'])
            ->each(function (Movement $m) use (&$daily) {
                $daily[$m->date->toDateString()]['expense'] += (float) $m->amount;
            });

        return [
            'period' => $period,
            'isEmpty' => ! $base()->exists(),
            'averageIncome' => round($totalIncome / $monthsToAnalyze, 2),
            'averageExpense' => round($totalExpense / $monthsToAnalyze, 2),
            'savingsRate' => $totalIncome > 0 ? round((($totalIncome - $totalExpense) / $totalIncome) * 100, 2) : 0,
            'bestMonth' => $bestMonth,
            'monthlyData' => $monthly,
            'categoryBreakdown' => $breakdown,
            'dailyData' => array_values($daily),
        ];
    }
}
