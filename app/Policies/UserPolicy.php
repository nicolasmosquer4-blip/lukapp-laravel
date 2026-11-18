<?php

namespace App\Policies;

use App\Models\User;

/** Acciones administrativas sobre cuentas: las cuentas admin están protegidas. */
class UserPolicy
{
    public function manage(User $admin, User $target): bool
    {
        return $admin->isAdmin() && ! $target->isAdmin();
    }
}
