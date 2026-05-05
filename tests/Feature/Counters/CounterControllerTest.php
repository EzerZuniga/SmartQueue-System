<?php

use App\Models\Counter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

// Suite de pruebas para usuarios no autenticados
describe('unauthenticated users', function () {
    beforeEach(function () {
        // Popula la base de datos con los seeders. Necesario para que el middleware de Inertia funcione.
        $this->seed();
    });

    test('cannot access counter list', function () {
        // Verifica que un usuario no autenticado es redirigido al intentar ver la lista de mostradores.
        get(route('counters.index'))->assertRedirect(route('login'));
    });

    test('cannot create a counter', function () {
        // Verifica que un usuario no autenticado es redirigido al intentar crear un mostrador.
        post(route('counters.store'), [])->assertRedirect(route('login'));
    });

    test('cannot update a counter', function () {
        // Verifica que un usuario no autenticado es redirigido al intentar actualizar un mostrador.
        $counter = Counter::factory()->create();
        put(route('counters.update', $counter), [])->assertRedirect(route('login'));
    });

    test('cannot delete a counter', function () {
        // Verifica que un usuario no autenticado es redirigido al intentar eliminar un mostrador.
        $counter = Counter::factory()->create();
        delete(route('counters.destroy', $counter))->assertRedirect(route('login'));
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
    test('can view counter list', function () {
        // Verifica que un usuario autenticado puede ver la lista de mostradores.
        get(route('counters.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('counters/Index'));
    });

    test('can create a new counter', function () {
        // Prepara los datos para un nuevo mostrador.
        $counterData = [
            'name' => 'New Counter',
            'description' => 'Description for new counter',
            'is_active' => true,
        ];

        // Envía la petición para crear el mostrador.
        post(route('counters.store'), $counterData)
            ->assertRedirect(route('counters.index'));

        // Verifica que el mostrador fue creado en la base de datos.
        assertDatabaseHas('counters', ['name' => 'New Counter']);
    });

    test('can update a counter', function () {
        // Crea un mostrador para ser actualizado.
        $counterToUpdate = Counter::factory()->create();

        // Nuevos datos para el mostrador.
        $updatedData = [
            'name' => 'Updated Counter Name',
            'description' => 'Updated description',
            'is_active' => false,
        ];

        // Envía la petición para actualizar el mostrador.
        put(route('counters.update', $counterToUpdate), $updatedData)
            ->assertRedirect(route('counters.index'));

        // Verifica que los datos del mostrador fueron actualizados en la base de datos.
        assertDatabaseHas('counters', ['id' => $counterToUpdate->id, 'name' => 'Updated Counter Name']);
    });

    test('can delete a counter', function () {
        // Crea un mostrador para ser eliminado.
        $counterToDelete = Counter::factory()->create();

        // Envía la petición para eliminar el mostrador.
        delete(route('counters.destroy', $counterToDelete))
            ->assertRedirect(route('counters.index'));

        // Verifica que el mostrador fue eliminado de la base de datos.
        $this->assertDatabaseMissing('counters', [
            'id' => $counterToDelete->id,
            'deleted_at' => null,
        ]);

        // 2. Verificamos que SÍ existe en la BD (marcado como borrado)
        $this->assertSoftDeleted($counterToDelete);
    });

    // Pruebas de Validación
    test('counter creation requires a name', function () {
        // Envía una petición de creación sin nombre.
        post(route('counters.store'), [])
            ->assertSessionHasErrors(['name']);
    });

    test('counter creation requires a unique name', function () {
        // Crea un mostrador existente para probar la unicidad del nombre.
        Counter::factory()->create(['name' => 'Existing Counter']);

        // Intenta crear un mostrador con el mismo nombre.
        post(route('counters.store'), ['name' => 'Existing Counter'])
            ->assertSessionHasErrors(['name']);
    });

    test('counter update requires a name', function () {
        // Crea un mostrador para probar la actualización sin nombre.
        $counterToUpdate = Counter::factory()->create();

        // Envía una petición de actualización sin nombre.
        put(route('counters.update', $counterToUpdate), ['name' => ''])
            ->assertSessionHasErrors(['name']);
    });

    // Pruebas de Casos Límite
    test('updating a non-existent counter returns 404', function () {
        // Intenta actualizar un mostrador con un ID que no existe.
        put(route('counters.update', 999), ['name' => 'Non Existent'])
            ->assertStatus(404);
    });

    test('deleting a non-existent counter returns 404', function () {
        // Intenta eliminar un mostrador con un ID que no existe.
        delete(route('counters.destroy', 999))
            ->assertStatus(404);
    });
});
