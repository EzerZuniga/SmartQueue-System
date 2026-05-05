<?php

use App\Models\Counter;
use App\Models\CounterAssignment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

// Suite de pruebas para usuarios no autenticados
describe('unauthenticated users', function () {
    beforeEach(function () {
        // Popula la base de datos con los seeders.
        $this->seed();
    });

    test('cannot access counter assignment list', function () {
        // Verifica que un usuario no autenticado es redirigido al intentar ver la lista de asignaciones.
        get(route('counterAssignments.index'))->assertRedirect(route('login'));
    });

    test('cannot create a counter assignment', function () {
        // Verifica que un usuario no autenticado es redirigido al intentar crear una asignación.
        post(route('counterAssignments.store'), [])->assertRedirect(route('login'));
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
    test('can view counter assignment page', function () {
        // Crea un contador y servicios para que la página se renderice correctamente.
        Counter::factory()->create();
        Service::factory()->count(3)->create();

        CounterAssignment::factory()
            ->withServices(1)
            ->create([
                'user_id' => $this->user->id,
            ]);

        // Verifica que un usuario autenticado puede ver la página de asignaciones.
        get(route('counterAssignments.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('assignments/Index'));
    });

    test('can create a new counter assignment', function () {
        // Crea un contador y algunos servicios.
        $counter = Counter::factory()->create();
        $services = Service::factory()->count(2)->create();

        // Prepara los datos para una nueva asignación.
        $assignmentData = [
            'counter_id' => $counter->id,
            'user_id' => $this->user->id,
            'services' => $services->pluck('id')->toArray(),
        ];

        // Envía la petición para crear la asignación.
        post(route('counterAssignments.store'), $assignmentData)
            ->assertRedirect(route('counterAssignments.index'))
            ->assertSessionHas('success');

        // Verifica que la asignación fue creada en la base de datos.
        assertDatabaseHas('counter_assignments', [
            'counter_id' => $counter->id,
            'user_id' => $this->user->id,
        ]);

        // Verifica que los servicios fueron adjuntados a la asignación.
        foreach ($services as $service) {
            assertDatabaseHas('assignment_services', [
                'counter_assignment_id' => CounterAssignment::where('user_id', $this->user->id)->first()->id,
                'service_id' => $service->id,
            ]);
        }
    });

    // Pruebas de Validación
    test('counter assignment creation requires a counter', function () {
        // Envía una petición de creación sin counter_id.
        post(route('counterAssignments.store'), [
            'user_id' => $this->user->id,
            'service_ids' => [],
        ])->assertSessionHasErrors(['counter_id']);
    });

    test('counter assignment creation requires services', function () {
        // Crea un contador.
        $counter = Counter::factory()->create();

        // Envía una petición de creación sin service_ids.
        post(route('counterAssignments.store'), [
            'counter_id' => $counter->id,
            'user_id' => $this->user->id,
            'services' => [],
        ])->assertSessionHasErrors(['services']);
    });

    test('counter assignment creation requires valid counter and services', function () {
        // Envía una petición de creación con IDs inválidos.
        post(route('counterAssignments.store'), [
            'counter_id' => 999, // ID inexistente
            'user_id' => $this->user->id,
            'services' => [999], // ID inexistente
        ])->assertSessionHasErrors(['counter_id', 'services.0']);
    });

    test('user can only have one active counter assignment', function () {
        // Crea una asignación activa para el usuario.
        CounterAssignment::factory()->create(['user_id' => $this->user->id, 'closed_at' => null]);
        $counter = Counter::factory()->create();
        $services = Service::factory()->count(1)->create();

        // Intenta crear otra asignación activa para el mismo usuario.
        post(route('counterAssignments.store'), [
            'counter_id' => $counter->id,
            'services' => $services->pluck('id')->toArray(),
        ])->assertRedirect(route('counterAssignments.index'));

        // Sigue existiendo SOLO una asignación activa
        expect(
            CounterAssignment::where('user_id', $this->user->id)
                ->whereNull('closed_at')
                ->count()
        )->toBe(1);
    });
});
