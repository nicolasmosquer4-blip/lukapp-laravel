<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\GoalContribution;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** Port de metaModel.js → contributeToGoal(): abonos y retiros con notificación única al completar. */
class GoalService
{
    public function contribute(Goal $goal, string $type, float $amount, ?string $comment = null): Goal
    {
        return DB::transaction(function () use ($goal, $type, $amount, $comment) {
            // Bloqueo de fila para evitar condiciones de carrera con abonos simultáneos
            $goal = Goal::query()->lockForUpdate()->findOrFail($goal->id);
            $saved = (float) $goal->saved;

            if ($type === GoalContribution::TYPE_WITHDRAW && $amount > $saved) {
                throw new BadRequestHttpException('No puedes retirar más de lo ahorrado ('.AppNotification::money($saved).').');
            }

            $goal->saved = $type === GoalContribution::TYPE_DEPOSIT ? $saved + $amount : $saved - $amount;
            $goal->contributions()->create(['type' => $type, 'amount' => $amount, 'comment' => $comment]);

            if ($goal->isCompleted() && ! $goal->completed_notified) {
                $goal->completed_notified = true;
                $user = $goal->user;
                if ($user->settingsOrDefault()->notif_goals) {
                    $user->notify(new AppNotification('Meta alcanzada', "Has alcanzado tu meta \"{$goal->name}\".", 'success'));
                }
            } elseif (! $goal->isCompleted()) {
                $goal->completed_notified = false; // si baja del 100% se reinicia la bandera
            }

            $goal->save();

            return $goal;
        });
    }

    /** Si al editar el objetivo la meta queda completa/incompleta, se ajusta la bandera. */
    public function syncCompletionFlag(Goal $goal): void
    {
        if (! $goal->isCompleted() && $goal->completed_notified) {
            $goal->completed_notified = false;
            $goal->save();
        }
    }
}
