<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

/** Un usuario solo puede ver o modificar sus propias metas. */
class GoalPolicy
{
    public function view(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function update(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }
}
