<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear Roles
        // $adminRole = Role::create(['name' => 'Administrador']);
        $adminRole = Role::create(['name' => 'Administrador']);

        // 2. Definir Permisos (Resource-based)
        $permissions = [
            // Roles
            'roles.ver',
            'roles.crear',
            'roles.editar',
            'roles.eliminar',

            // Permisos
            'permisos.ver',
            'permisos.crear',
            'permisos.editar',
            'permisos.eliminar',

            // Usuarios
            'users.ver',
            'users.crear',
            'users.editar',
            'users.eliminar',

            // Ventanillas
            'counters.ver',
            'counters.crear',
            'counters.editar',
            'counters.eliminar',

            // Servicios
            'services.ver',
            'services.crear',
            'services.editar',
            'services.eliminar',

            // Estados de Atención
            'call_statuses.ver',

            // Asignaciones de Caja
            'assignments.ver',
            'assignments.crear',
            'assignments.eliminar',

            // Configuración
            'settings.ver',
            'settings.editar',

            // Atención (Pantalla Principal)
            'calls.ver',

            // Reportes
            'reportes.index',

        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // 3. Asignar Permisos a Roles
        // Administrador: Todo (o usar Gate::before)
        $adminRole->givePermissionTo(Permission::all());

        // 4. Asignar Roles a Usuarios
        // Usuario 1 -> Administrador
        $user1 = User::find(1);
        if ($user1) {
            $user1->assignRole($adminRole);
        }
    }
}
