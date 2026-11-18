<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\Movement;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** Port de adminModel.js con datos reales de MySQL. */
class AdminService
{
    public function kpis(): array
    {
        return [
            'totalUsers' => User::where('role', User::ROLE_USER)->count(),
            'totalMovements' => Movement::count(),
            'totalGoals' => Goal::count(),
            'totalNotifs' => DB::table('notifications')->count(),
        ];
    }

    public function userDetails(User $user): array
    {
        $user->loadCount(['movements', 'goals', 'notifications']);

        return [
            'movementsCount' => $user->movements_count,
            'goalsCount' => $user->goals_count,
            'notifsCount' => $user->notifications_count,
            'settings' => $user->settingsOrDefault()->toGroupedArray(),
        ];
    }

    public function globalStats(): array
    {
        $totals = Movement::query()->notCancelled()
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'ingreso' THEN amount ELSE 0 END), 0) AS income")
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'gasto' THEN amount ELSE 0 END), 0) AS expense")
            ->first();

        $start = Carbon::today()->startOfMonth()->subMonths(5);
        $labels = [];
        $counts = [];
        $buckets = [];
        for ($i = 0; $i < 6; $i++) {
            $d = $start->copy()->addMonths($i);
            $labels[] = ucfirst(rtrim($d->locale('es')->translatedFormat('M'), '.'));
            $buckets[$d->format('Y-m')] = $i;
            $counts[$i] = 0;
        }

        User::where('role', User::ROLE_USER)
            ->where('created_at', '>=', $start)
            ->pluck('created_at')
            ->each(function ($createdAt) use (&$counts, $buckets) {
                $key = Carbon::parse($createdAt)->format('Y-m');
                if (isset($buckets[$key])) {
                    $counts[$buckets[$key]]++;
                }
            });

        return [
            'totalIncome' => (float) $totals->income,
            'totalExpense' => (float) $totals->expense,
            'userLabels' => $labels,
            'usersByMonth' => $counts,
        ];
    }

    public function systemMetrics(): array
    {
        $connection = DB::connection();
        $status = 'ok';
        $sizeKb = null;
        $tables = null;

        try {
            $connection->getPdo();
            if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
                // Solo las tablas de la base de datos de LukApp (no las de otros esquemas del servidor)
                $info = DB::selectOne(
                    'SELECT COUNT(*) AS total, COALESCE(SUM(data_length + index_length), 0) AS size FROM information_schema.tables WHERE table_schema = ?',
                    [$connection->getDatabaseName()]
                );
                $tables = (int) $info->total;
                $sizeKb = (int) round($info->size / 1024);
            } else {
                $tables = count(Schema::getTables());
            }
        } catch (Throwable) {
            $status = 'error';
        }

        return [
            'database' => [
                'status' => $status,
                'driver' => $connection->getDriverName(),
                'name' => $connection->getDatabaseName(),
                'tables' => $tables,
                'sizeKB' => $sizeKb,
            ],
            'version' => [
                'app' => config('app.version', '2.1.0'),
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
            ],
            'demoStatus' => SystemSetting::demoModeEnabled(),
        ];
    }
}
