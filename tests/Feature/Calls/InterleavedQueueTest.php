<?php

use App\Http\Controllers\CallController;
use App\Models\Call;
use App\Models\CallStatuse;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Usamos seeders para los estados de llamada y configuración.
    $this->seed();

    // Creamos un usuario y un servicio base para las pruebas.
    $this->user = User::factory()->create();
    $this->service = Service::factory()->create();

    // Obtenemos los IDs de los estados que usaremos en las pruebas.
    // Esto evita que las factorías intenten crear estados que ya existen.
    $this->waitingStatusId = CallStatuse::where('slug', 'waiting')->first()->id;
    $this->completedStatusId = CallStatuse::where('slug', 'completed')->first()->id;

    // Instancia del controlador que vamos a probar.
    $this->controller = new CallController;
});

test('queue starts with VIP if no previous calls exist', function () {
    // Creamos 2 VIP y 2 Normales, todos en estado 'waiting'.
    Ticket::factory()->create(['service_id' => $this->service->id, 'priority' => 1, 'call_status_id' => $this->waitingStatusId, 'created_at' => now()->subMinutes(4)]);
    Ticket::factory()->create(['service_id' => $this->service->id, 'priority' => 0, 'call_status_id' => $this->waitingStatusId, 'created_at' => now()->subMinutes(3)]);
    Ticket::factory()->create(['service_id' => $this->service->id, 'priority' => 1, 'call_status_id' => $this->waitingStatusId, 'created_at' => now()->subMinutes(2)]);
    Ticket::factory()->create(['service_id' => $this->service->id, 'priority' => 0, 'call_status_id' => $this->waitingStatusId, 'created_at' => now()->subMinutes(1)]);

    // Llamamos a la función privada.
    $queue = $this->controller->testGetInterleavedQueue([$this->service->id], $this->user->id);

    // El primero en la cola DEBE ser VIP por defecto.
    expect($queue->first()->priority)->toBe(1);
    // El orden debe ser V, N, V, N.
    expect($queue->pluck('priority')->toArray())->toBe([1, 0, 1, 0]);
});

test('queue starts with Normal if last call was VIP', function () {
    // Simulamos una llamada previa a un ticket VIP por el mismo usuario.
    // El ticket de esta llamada ya está 'completed' para no interferir con la cola.
    $lastVipTicket = Ticket::factory()->create(['priority' => 1, 'call_status_id' => $this->completedStatusId]);
    Call::factory()->create([
        'user_id' => $this->user->id,
        'ticket_id' => $lastVipTicket->id,
    ]);

    // Creamos la cola actual en estado 'waiting'.
    Ticket::factory()->create(['service_id' => $this->service->id, 'priority' => 1, 'call_status_id' => $this->waitingStatusId]);
    Ticket::factory()->create(['service_id' => $this->service->id, 'priority' => 0, 'call_status_id' => $this->waitingStatusId]);

    // Llamamos a la función.
    $queue = $this->controller->testGetInterleavedQueue([$this->service->id], $this->user->id);

    // El primero DEBE ser Normal porque el último fue VIP.
    expect($queue->first()->priority)->toBe(0);
    // El orden debe ser N, V.
    expect($queue->pluck('priority')->toArray())->toBe([0, 1]);
});

test('queue starts with VIP if last call was Normal', function () {
    // Simulamos una llamada previa a un ticket Normal.
    $lastNormalTicket = Ticket::factory()->create(['priority' => 0, 'call_status_id' => $this->completedStatusId]);
    Call::factory()->create([
        'user_id' => $this->user->id,
        'ticket_id' => $lastNormalTicket->id,
    ]);

    // Creamos la cola actual en estado 'waiting'.
    Ticket::factory()->create(['service_id' => $this->service->id, 'priority' => 1, 'call_status_id' => $this->waitingStatusId]);
    Ticket::factory()->create(['service_id' => $this->service->id, 'priority' => 0, 'call_status_id' => $this->waitingStatusId]);

    // Llamamos a la función.
    $queue = $this->controller->testGetInterleavedQueue([$this->service->id], $this->user->id);

    // El primero DEBE ser VIP porque el último fue Normal.
    expect($queue->first()->priority)->toBe(1);
    // El orden debe ser V, N.
    expect($queue->pluck('priority')->toArray())->toBe([1, 0]);
});

test('queue handles only VIP tickets correctly', function () {
    // Solo creamos tickets VIP en estado 'waiting'.
    Ticket::factory()->count(3)->create(['service_id' => $this->service->id, 'priority' => 1, 'call_status_id' => $this->waitingStatusId]);

    $queue = $this->controller->testGetInterleavedQueue([$this->service->id], $this->user->id);

    // La cola debe tener 3 tickets.
    expect($queue)->toHaveCount(3);
    // Todos deben ser VIP.
    expect($queue->every('priority', '==', 1))->toBeTrue();
});

test('queue handles only Normal tickets correctly', function () {
    // Solo creamos tickets Normales en estado 'waiting'.
    Ticket::factory()->count(3)->create(['service_id' => $this->service->id, 'priority' => 0, 'call_status_id' => $this->waitingStatusId]);

    $queue = $this->controller->testGetInterleavedQueue([$this->service->id], $this->user->id);

    // La cola debe tener 3 tickets.
    expect($queue)->toHaveCount(3);
    // Todos deben ser Normales.
    expect($queue->every('priority', '==', 0))->toBeTrue();
});

test('returns an empty collection when no tickets are waiting', function () {
    $queue = $this->controller->testGetInterleavedQueue([$this->service->id], $this->user->id);

    // La cola debe estar vacía.
    expect($queue)->toBeEmpty();
});
