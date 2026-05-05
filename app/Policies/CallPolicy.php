<?php

namespace App\Policies;

use App\Models\User;

class CallPolicy
{
    /**
     * Un único permiso para todas las operaciones de atención
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('calls.ver');
    }
}
