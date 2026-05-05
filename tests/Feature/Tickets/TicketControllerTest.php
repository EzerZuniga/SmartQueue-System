<?php

use App\Events\TicketCreated;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Popula la base de datos con los seeders (CallStatuseSeeder, SettingSeeder)
    $this->seed();
});

// Suite de pruebas para el Kiosko de creación de tickets (página pública)
describe('Ticket Kiosk', function () {

    test('cannot access kiosk with an invalid token', function () {
        // Intenta acceder al kiosko con un token incorrecto.
        get(route('tickets.create', ['token' => 'invalid-token']))
            ->assertStatus(404);
    });

    test('can access kiosk with a valid token', function () {
        // Obtiene el token válido desde la configuración.
        $validToken = Setting::first()->kiosk_token;

        // Accede al kiosko con el token correcto.
        get(route('tickets.create', ['token' => $validToken]))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('tickets/Create'));
    });

    test('displays only active services on the kiosk', function () {
        // Crea servicios de prueba: 2 activos y 1 inactivo.
        Service::factory()->count(2)->create(['status' => true]);
        Service::factory()->create(['status' => false]);

        $validToken = Setting::first()->kiosk_token;

        // Verifica que solo los servicios activos se pasan al componente de Inertia.
        get(route('tickets.create', ['token' => $validToken]))
            ->assertInertia(fn ($page) => $page->has('services', 2));
    });

    test('can create a new ticket (happy path)', function () {
        Event::fake(); // Evita que los eventos se disparen realmente.

        // Crea un servicio necesario para la creación del ticket.
        $service = Service::factory()->create(['prefix' => 'A', 'start_number' => 1]);
        $validToken = Setting::first()->kiosk_token;

        $ticketData = [
            'service_id' => $service->id,
            'client_name' => 'John Doe',
        ];

        // Envía la petición para crear el ticket.
        post(route('tickets.store', ['token' => $validToken]), $ticketData)
            ->assertRedirect()
            ->assertSessionHas('ticket_created');

        // Verifica que el ticket fue creado en la base de datos.
        assertDatabaseHas('tickets', [
            'service_id' => $service->id,
            'client_name' => 'John Doe',
            'ticket_number' => 'A-001', // Verifica la correcta generación del número.
        ]);

        // Verifica que el evento TicketCreated fue despachado.
        Event::assertDispatched(TicketCreated::class);
    });

    test('creation requires a valid service_id', function () {
        $validToken = Setting::first()->kiosk_token;

        // Envía la petición sin un service_id.
        post(route('tickets.store', ['token' => $validToken]), ['service_id' => 999])
            ->assertSessionHasErrors(['service_id']);
    });

    test('dynamic validation for client name works', function () {
        // Crea un servicio que requiere el nombre del cliente.
        $service = Service::factory()->create(['ask_name' => true, 'name_required' => true]);
        $validToken = Setting::first()->kiosk_token;

        // Intenta crear un ticket sin el nombre del cliente.
        post(route('tickets.store', ['token' => $validToken]), ['service_id' => $service->id])
            ->assertSessionHasErrors(['client_name']);
    });

    test('zipper logic for ticket position works correctly', function () {
        // Crea un servicio de prueba.
        $service = Service::factory()->create();
        $validToken = Setting::first()->kiosk_token;

        // 1. Crea un ticket normal (debería tener posición 2).
        post(route('tickets.store', ['token' => $validToken]), ['service_id' => $service->id, 'priority' => 0]);
        assertDatabaseHas('tickets', ['service_id' => $service->id, 'position' => 2]);

        // 2. Crea un ticket preferencial (debería tener posición 1).
        post(route('tickets.store', ['token' => $validToken]), ['service_id' => $service->id, 'priority' => 1]);
        assertDatabaseHas('tickets', ['service_id' => $service->id, 'position' => 1]);

        // 3. Crea otro ticket normal (debería tener posición 4).
        post(route('tickets.store', ['token' => $validToken]), ['service_id' => $service->id, 'priority' => 0]);
        $latestTicket = Ticket::where('service_id', $service->id)->orderBy('id', 'desc')->first();
        expect($latestTicket->position)->toBe(4);

        // 4. Crea otro ticket preferencial (debería tener posición 3).
        post(route('tickets.store', ['token' => $validToken]), ['service_id' => $service->id, 'priority' => 1]);
        $latestTicket = Ticket::where('service_id', $service->id)->orderBy('id', 'desc')->first();
        expect($latestTicket->position)->toBe(3);
    });
});
