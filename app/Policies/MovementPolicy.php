<?php

namespace App\Policies;

use App\Models\Movement;
use App\Models\User;

/** Un usuario solo puede ver o modificar sus propios movimientos. */
class MovementPolicy
{
    public function view(User $user, Movement $movement): bool
    {
        return $movement->user_id === $user->id;
    }

    public function update(User $user, Movement $movement): bool
    {
        return $movement->user_id === $user->id;
    }

    public function delete(User $user, Movement $movement): bool
    {
        return $movement->user_id === $user->id;
    }
}
