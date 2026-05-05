<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);
        // 1. Definir opciones válidas y capturar el valor (default: 5)
        $perPage = $request->input('perPage', 5);
        $validPerPage = [5, 10, 20, 50, 100];

        // Si envían algo raro, forzamos a 5
        $perPage = in_array($perPage, $validPerPage) ? $perPage : 5;

        $roles = Role::query()
            ->withCount('permissions') // Para mostrar cuántos permisos tiene
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('roles/Index', [
            'roles' => $roles,
            'filters' => [
                'search' => $request->input('search'),
                'perPage' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Role::class);
        $permissions = Permission::all()->groupBy(function ($permission) {
            // Agrupar permisos por recurso (ej: users.view -> users)
            return explode('.', $permission->name)[0];
        });

        return Inertia::render('roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name', // Validar que los permisos existan
        ]);

        $role = Role::create(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Rol creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): Response
    {
        $this->authorize('update', $role);
        $role->load('permissions'); // Cargar permisos actuales

        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return Inertia::render('roles/Edit', [
            'role' => $role,
            'currentPermissions' => $role->permissions->pluck('name'),
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        } else {
            // Si no envían permisos (array vacío), quitamos todos para evitar confusión
            // Ojo: en HTML forms, si no marcas checkboxes, no envía el array. Mejor manejarlo con cuidado.
            // Aquí asumimos que el frontend envía array vacío si no hay permisos.
            $role->syncPermissions([]);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Rol actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);
        // Evitar borrar Admin (opcional pero recomendado)
        if ($role->name === 'Administrador' || $role->id === 1) {
            return redirect()->back()->with('error', 'No puedes eliminar el rol de Administrador.');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Rol eliminado.');
    }
}
