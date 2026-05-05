<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('permisos.ver');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('permisos.crear');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('permisos.editar');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('permisos.eliminar');
    }
}
