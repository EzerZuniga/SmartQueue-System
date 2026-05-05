<?php

namespace App\Policies;

use App\Models\Counter;
use App\Models\User;

class CounterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('counters.ver');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('counters.crear');
    }

    public function update(User $user, Counter $counter): bool
    {
        return $user->hasPermissionTo('counters.editar');
    }

    public function delete(User $user, Counter $counter): bool
    {
        return $user->hasPermissionTo('counters.eliminar');
    }
}
