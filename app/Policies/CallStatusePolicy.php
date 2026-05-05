<?php

namespace App\Policies;

use App\Models\User;

class CallStatusePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('call_statuses.ver');
    }

    // No create, update, or delete as per requirements
}
