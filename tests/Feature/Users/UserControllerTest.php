<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

// Suite de pruebas para usuarios no autenticados
describe('unauthenticated users', function () {
    beforeEach(function () {
        // Popula la base de datos con los seeders. Es necesario para que el middleware de Inertia funcione.
        $this->seed();
    });

    test('cannot access user list', function () {
        get(route('users.index'))->assertRedirect(route('login'));
    });

    test('cannot create a user', function () {
        post(route('users.store'), [])->assertRedirect(route('login'));
    });

    test('cannot update a user', function () {
        $user = User::factory()->create();
        put(route('users.update', $user), [])->assertRedirect(route('login'));
    });

    test('cannot delete a user', function () {
        $user = User::factory()->create();
        delete(route('users.destroy', $user))->assertRedirect(route('login'));
    });
});

// Suite de pruebas para usuarios autenticados
describe('authenticated users', function () {
    beforeEach(function () {
        // Popula la base de datos con los seeders y autentica a un usuario.
        $this->seed();
        $this->user = User::factory()->create();
        actingAs($this->user);
    });

    // Pruebas "Happy Path"
    test('can view user list', function () {
        get(route('users.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('users/Index'));
    });

    test('can create a new user', function () {
        $userData = [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        post(route('users.store'), $userData)
            ->assertRedirect(route('users.index'));

        assertDatabaseHas('users', ['email' => 'new@example.com']);
    });

    test('can update a user', function () {
        $userToUpdate = User::factory()->create();
        $updatedData = ['name' => 'Updated Name', 'email' => 'updated@example.com'];

        put(route('users.update', $userToUpdate), $updatedData)
            ->assertRedirect(route('users.index'));

        assertDatabaseHas('users', ['id' => $userToUpdate->id, 'name' => 'Updated Name']);
    });

    test('can delete a user', function () {
        $userToDelete = User::factory()->create();

        delete(route('users.destroy', $userToDelete))
            ->assertRedirect(route('users.index'));

        assertSoftDeleted('users', ['id' => $userToDelete->id]);
    });

    // Pruebas de Validación
    test('creation requires name, email, and password', function () {
        post(route('users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    });

    test('creation requires a valid email', function () {
        post(route('users.store'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors(['email']);
    });

    test('creation requires password confirmation', function () {
        post(route('users.store'), ['password' => 'password'])
            ->assertSessionHasErrors(['password']);
    });

    // Pruebas de Casos Límite
    test('updating a non-existent user returns 404', function () {
        put(route('users.update', 999), ['name' => 'test', 'email' => 'test@test.com'])
            ->assertStatus(404);
    });

    test('deleting a non-existent user returns 404', function () {
        delete(route('users.destroy', 999))
            ->assertStatus(404);
    });
});
