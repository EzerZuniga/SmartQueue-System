<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.ver');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.crear');
    }

    public function update(User $user, User $model): bool
    {
        // Evitar editar al Administrador principal o a sí mismo si se requiere (opcional)
        // if ($model->id === 1) return false;
        return $user->hasPermissionTo('users.editar');
    }

    public function delete(User $user, User $model): bool
    {
        // Evitar auto-eliminación o eliminar al admin
        if ($model->id === 1 || $user->id === $model->id) {
            return false;
        }

        return $user->hasPermissionTo('users.eliminar');
    }
}
