<?php

namespace App\Policies;

use App\Models\CounterAssignment;
use App\Models\User;

class CounterAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('assignments.ver');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('assignments.crear');
    }

    public function delete(User $user, CounterAssignment $assignment): bool
    {
        // Solo puede eliminar (cerrar caja) si tiene permiso GENERAL de eliminar asignaciones
        // Ojo: Normalmente un cajero solo cierra SU asignación.
        // Si 'assignments.eliminar' es un permiso administrativo, el cajero común no podrá cerrar su caja.
        // Asumo que el requerimiento es estricto con los permisos.
        // Si el usuario quiere cerrar SU PROPIA caja, quizás debamos permitirlo siempre o checkear ID.
        // Por ahora nos ceñimos al plan: check permiso 'assignments.eliminar'.

        // CORRECCIÓN RAPIDA: Si es cerrar su propia asignación, debería poderse?
        // El plan dice: "delete: assignments.eliminar". Lo dejaré así.
        return $user->hasPermissionTo('assignments.eliminar');
    }
}
