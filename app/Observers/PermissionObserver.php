<?php

namespace App\Observers;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionObserver
{
    /**
     * Handle the Permission "created" event.
     */
    public function created(Permission $permission): void
    {
        $adminRole = Role::where('name', 'Administrador')
            ->where('guard_name', $permission->guard_name)
            ->first();

        // Si existe el rol para ese guard, asignamos.
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }
    }

    /**
     * Handle the Permission "deleted" event.
     */
    public function deleted(Permission $permission): void
    {
        // No es necesario desvincular manualmente si las migraciones de Spatie
        // usan onDelete('cascade') en la tabla pivot role_has_permissions.
        // Normalmente esto se maneja automáticamente a nivel de base de datos.
    }
}
