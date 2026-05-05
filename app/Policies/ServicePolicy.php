<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('services.ver');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('services.crear');
    }

    public function update(User $user, Service $service): bool
    {
        return $user->hasPermissionTo('services.editar');
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->hasPermissionTo('services.eliminar');
    }
}
