<?php

use App\Events\CallUpdated;
use App\Events\TicketCalled;
use App\Models\Call;
use App\Models\CallStatuse;
use App\Models\Counter;
use App\Models\CounterAssignment;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

// Suite de pruebas para acceso a llamadas (CallController)
describe('CallController access', function () {
    beforeEach(function () {
        // Popula la base de datos con los seeders.
        $this->seed();
    });

    // Pruebas para usuarios no autenticados
    describe('unauthenticated users', function () {
        test('cannot access call index', function () {
            // Verifica que un usuario no autenticado es redirigido al intentar ver el índice de llamadas.
            get(route('calls.index'))->assertRedirect(route('login'));
        });

        test('cannot call next ticket', function () {
            // Verifica que un usuario no autenticado es redirigido al intentar llamar al siguiente ticket.
            post(route('calls.call-next'))->assertRedirect(route('login'));
        });

        test('cannot recall ticket', function () {
            // Verifica que un usuario no autenticado es redirigido al intentar re-llamar un ticket.
            post(route('calls.recall'))->assertRedirect(route('login'));
        });

        test('cannot start serving ticket', function () {
            // Verifica que un usuario no autenticado es redirigido al intentar iniciar la atención.
            post(route('calls.start'))->assertRedirect(route('login'));
        });

        test('cannot finish serving ticket', function () {
            // Verifica que un usuario no autenticado es redirigido al intentar finalizar la atención.
            post(route('calls.finish'))->assertRedirect(route('login'));
        });

        test('cannot abandon ticket', function () {
            // Verifica que un usuario no autenticado es redirigido al intentar abandonar un ticket.
            post(route('calls.abandon'))->assertRedirect(route('login'));
        });
    });

    // Pruebas para usuarios autenticados
    describe('authenticated users', function () {
        beforeEach(function () {
            // Autentica a un usuario antes de cada prueba.
            $this->user = User::factory()->create();
            actingAs($this->user);
        });

        // Pruebas para el metodo index
        describe('index method', function () {
            test('redirects to counter assignment if no active assignment', function () {
                // No hay asignación de ventanilla para el usuario.
                get(route('calls.index'))
                    ->assertRedirect(route('counterAssignments.create'))
                    ->assertSessionHas('error', 'Debes asignarte una ventanilla primero.');
            });

            test('can view calls index with active assignment and no current call', function () {
                // Crea una asignación de ventanilla activa.
                $counter = Counter::factory()->create();
                $service = Service::factory()->create();
                CounterAssignment::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $counter->id,
                    'closed_at' => null,
                ])->services()->attach($service);

                get(route('calls.index'))
                    ->assertStatus(200)
                    ->assertInertia(fn ($page) => $page->component('calls/Index'));
            });

            test('can view calls index with active assignment and current call', function () {
                // Crea una asignación y una llamada activa.
                $counter = Counter::factory()->create();
                $service = Service::factory()->create();
                $assignment = CounterAssignment::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $counter->id,
                    'closed_at' => null,
                ]);
                $assignment->services()->attach($service);

                $waitingStatus = CallStatuse::firstOrCreate(
                    ['slug' => 'waiting'],
                    [
                        'name' => 'En espera',
                        'color' => 'oklch(0.704 0.191 22.216)',
                        'is_final' => false,
                    ]
                );
                $ticket = Ticket::factory()->create(['service_id' => $service->id, 'call_status_id' => $waitingStatus->id]);
                Call::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $counter->id,
                    'ticket_id' => $ticket->id,
                    'service_id' => $service->id,
                    'call_status_id' => $waitingStatus->id,
                ]);

                get(route('calls.index'))
                    ->assertStatus(200)
                    ->assertInertia(fn ($page) => $page->component('calls/Index'));
            });
        });

        // Pruebas para el método callNext
        describe('callNext method', function () {
            beforeEach(function () {
                Event::fake(); // Falsifica los eventos para evitar que se disparen realmente.

                // Crea una asignación de ventanilla activa para el usuario.
                $this->counter = Counter::factory()->create();
                $this->service = Service::factory()->create();

                $this->assignment = CounterAssignment::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $this->counter->id,
                    'closed_at' => null,
                ]);
                $this->assignment->services()->attach([$this->service->id]);

                // Crea estados de llamada necesarios(No porque se corren los seeders)
                /* CallStatuse::factory()->create(['slug' => 'waiting']);
                CallStatuse::factory()->create(['slug' => 'calling']);
                CallStatuse::factory()->create(['slug' => 'in_progress']);
                CallStatuse::factory()->create(['slug' => 'completed']);
                CallStatuse::factory()->create(['slug' => 'no_show']);*/
            });

            test('redirects with error if no active assignment', function () {
                // Elimina la asignación para esta prueba especÃ­fica.
                $this->assignment->delete();

                post(route('calls.call-next'))
                    ->assertRedirect()
                    ->assertSessionHas('error', 'No tienes asignación.');
            });

            test('redirects with info if no tickets in queue', function () {
                post(route('calls.call-next'))
                    ->assertRedirect()
                    ->assertSessionHas('info', 'No hay tickets en espera.');

                Event::assertNotDispatched(TicketCalled::class);
                Event::assertNotDispatched(CallUpdated::class);
            });

            test('calls next available ticket and updates statuses', function () {
                $waitingStatus = CallStatuse::where('slug', 'waiting')->first();
                $callingStatus = CallStatuse::where('slug', 'calling')->first();

                // Crea un ticket en espera.
                $ticket = Ticket::factory()->create([
                    'service_id' => $this->service->id,
                    'call_status_id' => $waitingStatus->id,
                ]);

                post(route('calls.call-next'))
                    ->assertRedirect();

                // Verifica que se creó un registro de llamada.
                assertDatabaseHas('calls', [
                    'ticket_id' => $ticket->id,
                    'user_id' => $this->user->id,
                    'call_status_id' => $callingStatus->id,
                ]);

                // Verifica que el ticket actualizó su estado.
                assertDatabaseHas('tickets', [
                    'id' => $ticket->id,
                    'call_status_id' => $callingStatus->id,
                ]);

                // Verifica que los eventos fueron disparados.
                Event::assertDispatched(TicketCalled::class);
                Event::assertDispatched(CallUpdated::class);
            });

            // Hacer test Unitario para esto
            /*test('interleaves tickets by priority (VIP first then normal)', function () {
                actingAs($this->user);
                Call::where('user_id', $this->user->id)->delete();
                $waitingStatus = CallStatuse::where('slug', 'waiting')->first();

                // Crea tickets: 1 VIP, 1 Normal, 1 VIP, 1 Normal
                $normalTicket1 = Ticket::factory()->create([
                    'service_id' => $this->service->id,
                    'call_status_id' => $waitingStatus->id,
                    'priority' => 0,
                    'created_at' => now()->subMinutes(3),
                ]);
                $vipTicket1 = Ticket::factory()->create([
                    'service_id' => $this->service->id,
                    'call_status_id' => $waitingStatus->id,
                    'priority' => 1,
                    'created_at' => now()->subMinutes(4),
                ]);
                $normalTicket2 = Ticket::factory()->create([
                    'service_id' => $this->service->id,
                    'call_status_id' => $waitingStatus->id,
                    'priority' => 0,
                    'created_at' => now()->subMinutes(1),
                ]);
                $vipTicket2 = Ticket::factory()->create([
                    'service_id' => $this->service->id,
                    'call_status_id' => $waitingStatus->id,
                    'priority' => 1,
                    'created_at' => now()->subMinutes(2),
                ]);

                post(route('calls.call-next'));
                expect(Call::latest()->first()->ticket_id)->toBe($vipTicket1->id);

                post(route('calls.call-next'));
                expect(Call::latest()->first()->ticket_id)->toBe($normalTicket1->id);

                post(route('calls.call-next'));
                expect(Call::latest()->first()->ticket_id)->toBe($vipTicket2->id);

                post(route('calls.call-next'));
                expect(Call::latest()->first()->ticket_id)->toBe($normalTicket2->id);
            });*/
        });

        // Pruebas para el método recall
        describe('recall method', function () {
            beforeEach(function () {
                Event::fake(); // Falsifica los eventos

                // Crea una asignación y una llamada activa para el usuario.
                $this->counter = Counter::factory()->create();
                $this->service = Service::factory()->create();
                $this->assignment = CounterAssignment::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $this->counter->id,
                    'closed_at' => null,
                ]);
                $this->assignment->services()->attach($this->service);

                $callingStatus = CallStatuse::where('slug', 'calling')->first();

                $this->ticket = Ticket::factory()->create(['service_id' => $this->service->id]);
                $this->currentCall = Call::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $this->counter->id,
                    'ticket_id' => $this->ticket->id,
                    'service_id' => $this->service->id,
                    'call_status_id' => $callingStatus->id,
                ]);
            });

            test('can recall an active ticket', function () {
                post(route('calls.recall'))
                    ->assertRedirect()
                    ->assertSessionHas('info', 'Rellamando al cliente...');

                // Verifica que el evento TicketCalled fue disparado con la llamada correcta.
                Event::assertDispatched(TicketCalled::class, function ($event) {
                    return $event->call->id === $this->currentCall->id;
                });
            });

            test('returns 404 if no active call to recall', function () {
                // Cambia el estado de la llamada para que no sea activa.
                $completedStatusId = CallStatuse::where('slug', 'completed')->value('id');
                // $this->currentCall->callStatus->slug = 'completed';
                $this->currentCall->update([
                    'call_status_id' => $completedStatusId,
                ]);

                post(route('calls.recall'))
                    ->assertStatus(404); // Se espera un 404 si no encuentra la llamada activa.
            });
        });

        // Pruebas para el método start
        describe('start method', function () {
            beforeEach(function () {
                Event::fake(); // Falsifica los eventos

                // Crea una asignación y una llamada activa (calling) para el usuario.
                $this->counter = Counter::factory()->create();
                $this->service = Service::factory()->create();
                $this->assignment = CounterAssignment::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $this->counter->id,
                    'closed_at' => null,
                ]);
                $this->assignment->services()->attach($this->service);

                $callingStatus = CallStatuse::where('slug', 'calling')->first();

                $this->ticket = Ticket::factory()->create(['service_id' => $this->service->id]);
                $this->currentCall = Call::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $this->counter->id,
                    'ticket_id' => $this->ticket->id,
                    'service_id' => $this->service->id,
                    'call_status_id' => $callingStatus->id,
                ]);
            });

            test('can start serving an active ticket', function () {
                post(route('calls.start'))
                    ->assertRedirect();

                $inProgressStatus = CallStatuse::where('slug', 'in_progress')->first();

                // Verifica que el estado de la llamada se actualizó a 'in_progress'.
                assertDatabaseHas('calls', [
                    'id' => $this->currentCall->id,
                    'call_status_id' => $inProgressStatus->id,
                ]);
                assertDatabaseHas('tickets', [
                    'id' => $this->ticket->id,
                    'call_status_id' => $inProgressStatus->id,
                ]);

                Event::assertDispatched(CallUpdated::class);
            });

            test('returns 404 if no active call to start', function () {
                // Cambia el estado de la llamada para que no sea 'calling'.
                $completedStatus = CallStatuse::where('slug', 'completed')->first();
                $this->currentCall->update(['call_status_id' => $completedStatus->id]);

                post(route('calls.start'))
                    ->assertStatus(404);
            });
        });

        // Pruebas para el método finish
        describe('finish method', function () {
            beforeEach(function () {
                Event::fake(); // Falsifica los eventos

                // Crea una asignación y una llamada en progreso para el usuario.
                $this->counter = Counter::factory()->create();
                $this->service = Service::factory()->create();
                $this->assignment = CounterAssignment::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $this->counter->id,
                    'closed_at' => null,
                ]);
                $this->assignment->services()->attach($this->service);

                $inProgressStatus = CallStatuse::where('slug', 'in_progress')->first();

                $this->ticket = Ticket::factory()->create(['service_id' => $this->service->id]);
                $this->currentCall = Call::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $this->counter->id,
                    'ticket_id' => $this->ticket->id,
                    'service_id' => $this->service->id,
                    'call_status_id' => $inProgressStatus->id,
                    'called_at' => now()->subMinutes(5), // Ticket llamado hace 5 min
                    'started_at' => now()->subMinutes(2), // Atención iniciada hace 2 min
                ]);
            });

            test('can finish serving an in-progress ticket', function () {
                post(route('calls.finish'))
                    ->assertRedirect();

                $completedStatus = CallStatuse::where('slug', 'completed')->first();

                // Verifica que el estado de la llamada y el ticket se actualizaron a 'completed'.
                assertDatabaseHas('calls', [
                    'id' => $this->currentCall->id,
                    'call_status_id' => $completedStatus->id,
                ]);
                assertDatabaseHas('tickets', [
                    'id' => $this->ticket->id,
                    'call_status_id' => $completedStatus->id,
                ]);

                // Verifica que las duraciones se calcularon.
                $updatedCall = Call::find($this->currentCall->id);
                expect($updatedCall->served_duration)->toBeInt()->toBeGreaterThan(0);
                expect($updatedCall->turn_around_duration)->toBeInt()->toBeGreaterThan(0);

                Event::assertDispatched(CallUpdated::class);
            });

            test('returns 404 if no in-progress call to finish', function () {
                // Cambia el estado de la llamada para que no sea 'in_progress'.
                $callingStatus = CallStatuse::where('slug', 'calling')->first();
                $this->currentCall->update(['call_status_id' => $callingStatus->id]);

                post(route('calls.finish'))
                    ->assertStatus(404);
            });
        });

        // Pruebas para el método abandon
        describe('abandon method', function () {
            beforeEach(function () {
                Event::fake(); // Falsifica los eventos

                // Crea una asignación y una llamada activa (calling o in_progress) para el usuario.
                $this->counter = Counter::factory()->create();
                $this->service = Service::factory()->create();
                $this->assignment = CounterAssignment::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $this->counter->id,
                    'closed_at' => null,
                ]);
                $this->assignment->services()->attach($this->service);

                // $callingStatus = CallStatuse::factory()->create(['slug' => 'calling']);
                // $inProgressStatus = CallStatuse::factory()->create(['slug' => 'in_progress']);
                // $noShowStatus = CallStatuse::factory()->create(['slug' => 'no_show']);

                $this->ticket = Ticket::factory()->create(['service_id' => $this->service->id]);
            });

            test('can abandon a calling ticket', function () {
                // Crea una llamada en estado 'calling'.
                $callingStatus = CallStatuse::where('slug', 'calling')->first();
                $currentCall = Call::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $this->counter->id,
                    'ticket_id' => $this->ticket->id,
                    'service_id' => $this->service->id,
                    'call_status_id' => $callingStatus->id,
                    'waiting_duration' => 300,
                    'called_at' => now()->subMinutes(5),
                ]);

                post(route('calls.abandon'))
                    ->assertRedirect();

                $noShowStatus = CallStatuse::where('slug', 'no_show')->first();

                // Verifica que el estado de la llamada y el ticket se actualizaron a 'no_show'.
                assertDatabaseHas('calls', [
                    'id' => $currentCall->id,
                    'call_status_id' => $noShowStatus->id,
                ]);
                assertDatabaseHas('tickets', [
                    'id' => $this->ticket->id,
                    'call_status_id' => $noShowStatus->id,
                ]);

                // Verifica duraciones (served_duration debe ser 0 si no se inició).
                $updatedCall = Call::find($currentCall->id);
                expect($updatedCall->served_duration)->toBe(0);
                expect($updatedCall->turn_around_duration)->toBeInt()->toBeGreaterThan(0);

                Event::assertDispatched(CallUpdated::class);
            });

            test('can abandon an in-progress ticket', function () {
                // Crea una llamada en estado 'in_progress'.
                $inProgressStatus = CallStatuse::where('slug', 'in_progress')->first();
                $currentCall = Call::factory()->create([
                    'user_id' => $this->user->id,
                    'counter_id' => $this->counter->id,
                    'ticket_id' => $this->ticket->id,
                    'service_id' => $this->service->id,
                    'call_status_id' => $inProgressStatus->id,
                    'called_at' => now()->subMinutes(10),
                    'started_at' => now()->subMinutes(5),
                ]);

                post(route('calls.abandon'))
                    ->assertRedirect();

                $noShowStatus = CallStatuse::where('slug', 'no_show')->first();

                // Verifica que el estado de la llamada y el ticket se actualizaron a 'no_show'.
                assertDatabaseHas('calls', [
                    'id' => $currentCall->id,
                    'call_status_id' => $noShowStatus->id,
                ]);
                assertDatabaseHas('tickets', [
                    'id' => $this->ticket->id,
                    'call_status_id' => $noShowStatus->id,
                ]);

                // Verifica duraciones (served_duration debe ser mayor que 0).
                $updatedCall = Call::find($currentCall->id);
                expect($updatedCall->served_duration)->toBeInt()->toBeGreaterThan(0);
                expect($updatedCall->turn_around_duration)->toBeInt()->toBeGreaterThan(0);

                Event::assertDispatched(CallUpdated::class);
            });

            test('returns 404 if no active call to abandon', function () {
                // Asegura que no haya llamadas activas para el usuario.
                post(route('calls.abandon'))
                    ->assertStatus(404);
            });
        });
    });
});
