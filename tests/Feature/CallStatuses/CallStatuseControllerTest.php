<?php

use App\Models\CallStatuse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

// Suite de pruebas para el controlador de estados de llamada
describe('CallStatuseController', function () {
    beforeEach(function () {
        // Popula la base de datos con los seeders, que incluyen los estados de llamada predeterminados.
        $this->seed();
    });

    // Pruebas para usuarios no autenticados
    describe('unauthenticated users', function () {
        test('cannot access call status list', function () {
            // Verifica que un usuario no autenticado es redirigido al intentar ver la lista de estados.
            get(route('callStatuses.index'))->assertRedirect(route('login'));
        });

        /*  test('cannot update a call status', function () {
              // Crea un estado de llamada para intentar actualizar.
              $callStatus = CallStatuse::first();
              put(route('callStatuses.update', $callStatus), [])->assertRedirect(route('login'));
          });*/
    });

    // Pruebas para usuarios autenticados
    describe('authenticated users', function () {
        beforeEach(function () {
            // Autentica a un usuario antes de cada prueba.
            $this->user = User::factory()->create();
            actingAs($this->user);
        });

        // Pruebas "Happy Path"
        test('can view call status list', function () {
            // Verifica que un usuario autenticado puede ver la lista de estados de llamada.
            get(route('callStatuses.index'))
                ->assertStatus(200)
                ->assertInertia(fn ($page) => $page->component('callStatuses/Index'));
        });

        /*test('can update a call status', function () {
            // Crea un estado de llamada para ser actualizado.
            $callStatusToUpdate = CallStatuse::first();

            // Nuevos datos para el estado de llamada.
            $updatedData = [
                'name' => 'Updated Status Name',
                'description' => 'Updated description',
                'color' => '#FF00FF',
            ];

            // Envía la petición para actualizar el estado de llamada.
            put(route('callStatuses.update', $callStatusToUpdate), $updatedData)
                ->assertRedirect(route('call-statuses.index'))
                ->assertSessionHas('success');

            // Verifica que los datos del estado de llamada fueron actualizados en la base de datos.
            assertDatabaseHas('call_statuses', [
                'id' => $callStatusToUpdate->id,
                'name' => 'Updated Status Name',
            ]);
        });*/

        // Pruebas de Validación
        /*test('call status update requires a name', function () {
            // Crea un estado de llamada para probar la actualización sin nombre.
            $callStatusToUpdate = CallStatuse::first();

            // Envía una petición de actualización sin nombre.
            put(route('callStatuses.update', $callStatusToUpdate), ['name' => ''])
                ->assertSessionHasErrors(['name']);
        });*/

        /* test('call status update requires a unique slug', function () {
             // Crea dos estados de llamada.
             $existingCallStatus = CallStatuse::factory()->create(['slug' => 'test-slug']);
             $callStatusToUpdate = CallStatuse::factory()->create(['slug' => 'another-slug']);

             // Intenta actualizar el segundo estado con el slug del primero.
             put(route('callStatuses.update', $callStatusToUpdate), ['slug' => 'test-slug'])
                 ->assertSessionHasErrors(['slug']);
         });

         // Pruebas de Casos Límite
         test('updating a non-existent call status returns 404', function () {
             // Intenta actualizar un estado de llamada con un ID que no existe.
             put(route('callStatuses.update', 999), ['name' => 'Non Existent'])
                 ->assertStatus(404);
         });*/
    });
});
