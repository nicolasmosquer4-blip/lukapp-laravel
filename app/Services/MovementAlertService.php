<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Carbon;

/**
 * Notificaciones asociadas a movimientos (antes notifyIncome / notifyExpense /
 * notifySpendingAlert en notificationService.js), respetando las preferencias del usuario.
 */
class MovementAlertService
{
    /** Umbral: el gasto del mes en una categoría supera en 50% su promedio de los 3 meses previos. */
    private const UNUSUAL_FACTOR = 1.5;

    public function afterCreate(User $user, Movement $movement): void
    {
        $settings = $user->settingsOrDefault();

        if ($settings->notif_push) {
            $isIncome = $movement->type === Movement::TYPE_INCOME;
            $user->notify(new AppNotification(
                $isIncome ? 'Nuevo ingreso' : 'Nuevo gasto',
                $movement->description.': '.($isIncome ? '+' : '-').AppNotification::money($movement->amount).'.',
                $isIncome ? 'success' : 'info',
            ));
        }

        if ($settings->notif_alerts && $movement->type === Movement::TYPE_EXPENSE && $movement->status !== Movement::STATUS_CANCELLED) {
            $this->checkUnusualSpending($user, $movement);
        }
    }

    private function checkUnusualSpending(User $user, Movement $movement): void
    {
        $monthStart = $movement->date->copy()->startOfMonth();
        $alertKey = 'spending:'.$movement->category_id.':'.$monthStart->format('Y-m');

        // Máximo una alerta por categoría y mes
        if ($user->notifications()->where('data->alert_key', $alertKey)->exists()) {
            return;
        }

        $base = fn () => $user->movements()->notCancelled()
            ->where('type', Movement::TYPE_EXPENSE)
            ->where('category_id', $movement->category_id);

        $currentTotal = (float) $base()
            ->whereBetween('date', [$monthStart->toDateString(), $monthStart->copy()->endOfMonth()->toDateString()])
            ->sum('amount');

        $previousAverage = (float) $base()
            ->whereBetween('date', [
                $monthStart->copy()->subMonths(3)->toDateString(),
                $monthStart->copy()->subDay()->toDateString(),
            ])->sum('amount') / 3;

        if ($previousAverage > 0 && $currentTotal > $previousAverage * self::UNUSUAL_FACTOR) {
            $category = $movement->category->name;
            $user->notify(new AppNotification(
                'Alerta de gasto',
                "Tus gastos en {$category} este mes (".AppNotification::money($currentTotal).') superan tu promedio habitual.',
                'warning',
                null,
                ['alert_key' => $alertKey],
            ));
        }
    }
}
