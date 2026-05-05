<?php

use App\Models\Service;
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

    test('cannot access service list', function () {
        // Verifica que un usuario no autenticado es redirigido al intentar ver la lista de servicios.
        get(route('services.index'))->assertRedirect(route('login'));
    });

    test('cannot create a service', function () {
        // Verifica que un usuario no autenticado es redirigido al intentar crear un servicio.
        post(route('services.store'), [])->assertRedirect(route('login'));
    });

    test('cannot update a service', function () {
        // Verifica que un usuario no autenticado es redirigido al intentar actualizar un servicio.
        $service = Service::factory()->create();
        put(route('services.update', $service), [])->assertRedirect(route('login'));
    });

    test('cannot delete a service', function () {
        // Verifica que un usuario no autenticado es redirigido al intentar eliminar un servicio.
        $service = Service::factory()->create();
        delete(route('services.destroy', $service))->assertRedirect(route('login'));
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
    test('can view service list', function () {

        Service::factory()->count(3)->create();
        // Verifica que un usuario autenticado puede ver la lista de servicios.
        get(route('services.index'))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('services/Index') // Que cargue el componente Vue correcto
                    ->has('services.data', 3) // Que reciba 3 servicios en la paginación
            );
    });

    test('can create a new service', function () {
        // Prepara los datos para un nuevo servicio.
        $serviceData = [
            'name' => 'New Service',
            'prefix' => 'X',
            'start_number' => 1,
        ];

        // Envía la petición para crear el servicio.
        post(route('services.store'), $serviceData)
            ->assertRedirect(route('services.index'))
            ->assertSessionHas('success');

        // Verifica que el servicio fue creado en la base de datos.
        assertDatabaseHas('services', ['name' => 'New Service']);
    });

    test('can update a service', function () {
        // Crea un servicio para ser actualizado.
        $serviceToUpdate = Service::factory()->create();

        // Nuevos datos para el servicio.
        $updatedData = [
            'name' => 'Updated Service Name',
            'prefix' => 'Y',
            'start_number' => 101,
        ];

        // Envía la petición para actualizar el servicio.
        put(route('services.update', $serviceToUpdate), $updatedData)
            ->assertRedirect(route('services.index'));

        // Verifica que los datos del servicio fueron actualizados en la base de datos.
        assertDatabaseHas('services', ['id' => $serviceToUpdate->id, 'name' => 'Updated Service Name']);
    });

    test('can delete a service', function () {
        // Crea un servicio para ser eliminado.
        $serviceToDelete = Service::factory()->create();

        // Envía la petición para eliminar el servicio.
        delete(route('services.destroy', $serviceToDelete))
            ->assertRedirect(route('services.index'));

        // Verifica que el servicio no fue eliminado de la base de datos.
        $this->assertDatabaseMissing('counters', [
            'id' => $serviceToDelete->id,
            'deleted_at' => null,
        ]);

        // 2. Verificamos que SÍ existe en la BD (marcado como borrado)
        $this->assertSoftDeleted($serviceToDelete);
    });

    // Pruebas de Validación
    test('service creation requires a name', function () {
        // Envía una petición de creación sin nombre.
        post(route('services.store'), ['letter' => 'A'])
            ->assertSessionHasErrors(['name']);
    });

    test('service creation requires a unique letter', function () {
        // Crea un servicio existente para probar la unicidad de la letra.
        Service::factory()->create([
            'name' => 'New Service',
            'prefix' => 'A',
            'start_number' => 1,
        ]);

        // Intenta crear un servicio con la misma letra.
        post(route('services.store'), [
            'name' => 'New Service',
            'prefix' => 'A',
            'start_number' => 1,
        ])
            ->assertSessionHasErrors(['prefix']);
    });

    test('service update requires a name', function () {
        // Crea un servicio para probar la actualización sin nombre.
        $serviceToUpdate = Service::factory()->create();

        // Envía una petición de actualización sin nombre.
        put(route('services.update', $serviceToUpdate), ['name' => ''])
            ->assertSessionHasErrors(['name']);
    });

    // Pruebas de Casos Límite
    test('updating a non-existent service returns 404', function () {
        // Intenta actualizar un servicio con un ID que no existe.
        put(route('services.update', 999), ['name' => 'Non Existent', 'letter' => 'Z'])
            ->assertStatus(404);
    });

    test('deleting a non-existent service returns 404', function () {
        // Intenta eliminar un servicio con un ID que no existe.
        delete(route('services.destroy', 999))
            ->assertStatus(404);
    });

    test('can search services by name', function () {
        // Creamos datos "ruido" (que no deberían salir)
        Service::factory()->create(['name' => 'Licencia de Conducir']);

        // Creamos el dato "objetivo"
        Service::factory()->create(['name' => 'Pasaporte Express']);

        // Buscamos "Pasaporte"
        get(route('services.index', ['search' => 'Pasaporte']))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('services/Index')
                    ->has('services.data', 1) // Debe haber solo 1 resultado
                    ->where('services.data.0.name', 'Pasaporte Express') // Y debe ser el correcto
            );
    });
});
