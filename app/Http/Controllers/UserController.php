<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);
        $perPage = $request->input('perPage', 5);
        $perPage = in_array($perPage, [5, 10, 20, 50]) ? $perPage : 5;

        $users = User::query()
            ->with(['roles']) // Eager load roles
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search'),
                'perPage' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('users/Create', [
            'roles' => Role::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'image' => 'nullable|image|max:2048', // Max 2MB
            'status' => 'boolean',
            'role' => 'nullable|string|exists:roles,name', // Validar rol
        ]);

        // Manejo de Imagen
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('users');
        }

        // Hashing de Password
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        if ($request->filled('role')) {
            $user->assignRole($request->role); // Asignar rol
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function show() {}

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);
        $user->load('roles'); // Cargar roles del usuario

        return Inertia::render('users/Edit', [
            'user' => $user,
            'roles' => Role::all(), // Roles disponibles
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'image' => 'nullable|image|max:2048',
            'status' => 'boolean',
            'role' => 'nullable|string|exists:roles,name', // Validar rol
        ]);

        // Manejo de Imagen (Borrar anterior si hay nueva)
        if ($request->hasFile('image')) {
            if ($user->image_path) {
                Storage::disk('public')->delete($user->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('users', 'public');
        }

        // Manejo de Password (Solo actualizar si se envió algo)
        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']); // No tocar la password si viene vacía
        }

        $user->update($validated);
        if ($request->filled('role')) {
            $user->syncRoles($request->role); // Sincronizar (reemplazar) rol
        }

        return redirect()->route('users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        // Opcional: Borrar imagen al eliminar (o dejarla si usas SoftDeletes como backup)
        // if ($user->image_path) Storage::disk('public')->delete($user->image_path);

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
