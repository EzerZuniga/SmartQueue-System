<?php

namespace App\Http\Controllers;

use App\Events\CallUpdated;
use App\Events\TicketCalled;
use App\Events\TicketCreated;
use App\Models\Call;
use App\Models\CallStatuse;
use App\Models\CounterAssignment;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CallController extends Controller
{
    use AuthorizesRequests;

    public function index(): RedirectResponse|Response
    {
        $this->authorize('viewAny', Call::class);
        $user = Auth::user();

        // 1. Validar que el usuario tenga una asignación activa (Ventanilla abierta)
        $assignment = CounterAssignment::where('user_id', $user->id)
            ->whereNull('closed_at')
            ->with('counter')
            ->first();

        if (! $assignment) {
            return redirect()->route('counterAssignments.create')
                ->with('error', 'Debes asignarte una ventanilla primero.');
        }

        // 2. Buscar si el operador tiene una LLAMADA ACTIVA (no finalizada)
        // Estados: Calling (Llamando) o In_progress (Atendiendo)
        $statusesActive = CallStatuse::whereIn('slug', ['calling', 'in_progress'])->pluck('id');

        $currentCall = Call::where('user_id', $user->id)
            ->whereIn('call_status_id', $statusesActive)
            ->with(['ticket', 'service', 'callStatus'])
            ->latest()
            ->first();

        // 3. Obtener la Cola de Espera (Intercalada)
        $serviceIds = $assignment->services->pluck('id');

        // A. Conteo Total (Esto sigue siendo una query rápida)
        $totalWaitingCount = Ticket::whereIn('service_id', $serviceIds)
            ->waiting()
            ->today()
            ->count();

        // B. Obtener la LISTA INTERCALADA
        $interleavedCollection = $this->getInterleavedQueue($serviceIds, $user->id);

        // C. Preparamos para la vista (Limitamos a 10 y cargamos la relación 'service')
        $queue = $interleavedCollection->take(10);

        return Inertia::render('calls/Index', [
            'assignment' => $assignment,
            'currentCall' => $currentCall,
            'queue' => $queue->values(),
            'queueCount' => $totalWaitingCount,
            'services' => Service::where('status', true)->get(),
        ]);
    }

    public function callNext(): RedirectResponse
    {
        $this->authorize('viewAny', Call::class);
        $user = Auth::user();
        $assignment = $user->currentAssignment;
        if (! $assignment) {
            return back()->with('error', 'No tienes asignación.');
        }

        $serviceIds = $assignment->services->pluck('id');

        // Transacción para asegurar integridad
        return DB::transaction(function () use ($user, $assignment, $serviceIds) {

            // Obtenemos la lista ordenada en memoria (Cremallera)
            $orderedQueue = $this->getInterleavedQueue($serviceIds, $user->id);

            //  EL BUCLE DE INTENTOS (LA SOLUCIÓN)
            // En lugar de tomar solo el first(), recorremos la lista hasta poder bloquear uno.
            $nextTicket = null;

            foreach ($orderedQueue as $candidate) {
                // Intentamos bloquear este candidato específico
                $lockedCandidate = Ticket::where('id', $candidate->id)
                    ->waiting()
                    ->lockForUpdate()
                    ->first();

                if ($lockedCandidate) {
                    $nextTicket = $lockedCandidate;
                    break;
                }
                // Si $lockedCandidate es null, significa que otro cajero lo ganó
                // en el milisegundo anterior. El bucle continuará con el siguiente candidato.
            }

            if (! $nextTicket) {
                return back()->with('info', 'No hay tickets en espera.');
            }

            // 2. Obtener IDs de estados
            $statusCalled = CallStatuse::where('slug', 'calling')->firstOrFail();

            // 3. CREAR EL REGISTRO EN LA TABLA CALLS
            // Calculamos cuánto tiempo esperó el cliente (Waiting Duration)
            $waitingDuration = $nextTicket->created_at->diffInSeconds(now());

            $call = Call::create([
                'ticket_id' => $nextTicket->id,
                'service_id' => $nextTicket->service_id,
                'counter_id' => $assignment->counter_id,
                'user_id' => $user->id,
                'call_status_id' => $statusCalled->id,
                'token_letter' => $nextTicket->ticket_number, // Opcional si usas letras
                'token_number' => $nextTicket->number,
                'called_date' => today(),
                'called_at' => now(),
                'waiting_duration' => $waitingDuration,
            ]);

            // 4. ACTUALIZAR EL TICKET
            // Lo sacamos de la cola 'waiting' cambiando su estado
            $nextTicket->update([
                'call_status_id' => $statusCalled->id,
            ]);

            $call->load(['ticket', 'service', 'counter', 'callStatus']);

            // 5. DISPARAR EVENTOS
            TicketCreated::dispatch(); // No crea el ticket, pero si lanzamos el evento
            TicketCalled::dispatch($call);
            CallUpdated::dispatch($call);

            return back();
        });
    }

    public function recall(): RedirectResponse
    {
        $this->authorize('viewAny', Call::class);
        // / Solo redisparar el evento para que suene de nuevo en la TV
        $user = Auth::user();
        $statusesActive = CallStatuse::whereIn('slug', ['calling'])->pluck('id');

        $currentCall = Call::where('user_id', $user->id)
            ->whereIn('call_status_id', $statusesActive)
            ->with(['ticket', 'service', 'callStatus', 'counter'])
            ->latest()
            ->firstOrFail();

        TicketCalled::dispatch($currentCall);

        return back()->with('info', 'Rellamando al cliente...');
    }

    public function start(): RedirectResponse
    {
        $this->authorize('viewAny', Call::class);
        $user = Auth::user();

        $statusServing = CallStatuse::where('slug', 'in_progress')->firstOrFail();

        // Buscar la llamada actual en estado 'called'
        $currentCall = Call::where('user_id', $user->id)
            ->whereHas('callStatus', fn ($q) => $q->where('slug', 'calling'))
            ->latest()
            ->firstOrFail();

        // Actualizar Call
        $currentCall->update([
            'call_status_id' => $statusServing->id,
            'started_at' => now(),
        ]);

        // Actualizar Ticket (para que sepa que está siendo atendido)
        $currentCall->ticket->update([
            'call_status_id' => $statusServing->id,
        ]);

        // Avisar al Televisor
        CallUpdated::dispatch($currentCall);

        return back();
    }

    public function finish(): RedirectResponse
    {
        $this->authorize('viewAny', Call::class);
        $user = Auth::user();
        $statusServed = CallStatuse::where('slug', 'completed')->firstOrFail();

        // Buscar llamada 'serving' (En Atención)
        $currentCall = Call::where('user_id', $user->id)
            ->whereHas('callStatus', fn ($q) => $q->where('slug', 'in_progress'))
            ->latest()
            ->firstOrFail();

        // 1. Calcular duración de la atención (Desde que dio 'Start' hasta 'Ahora')
        $servedDuration = $currentCall->started_at->diffInSeconds(now());

        // 2. Calcular Turn Around (Espera acumulada + Atención actual)
        // Nota: waiting_duration ya lo calculaste y guardaste cuando hiciste 'callNext'
        $turnAroundDuration = $currentCall->waiting_duration + $servedDuration;

        // 3. Actualizar y Cerrar Call
        $currentCall->update([
            'call_status_id' => $statusServed->id,
            'ended_at' => now(),
            'served_duration' => $servedDuration,
            'turn_around_duration' => $turnAroundDuration, // <--- AQUÍ SE APLICA
        ]);

        // Cerrar Ticket
        $currentCall->ticket->update([
            'call_status_id' => $statusServed->id,
        ]);

        // Avisar al Televisor
        CallUpdated::dispatch($currentCall);

        return back();
    }

    public function abandon(): RedirectResponse
    {
        $this->authorize('viewAny', Call::class);
        $user = Auth::user();
        $statusNoShow = CallStatuse::where('slug', 'no_show')->firstOrFail();

        // Buscamos la llamada actual
        $currentCall = Call::where('user_id', $user->id)
            ->whereHas('callStatus', fn ($q) => $q->whereIn('slug', ['calling', 'in_progress']))
            ->latest()
            ->firstOrFail();

        // --- CÁLCULOS ---

        // 1. Calculamos si hubo tiempo de atención (si abandonó a mitad del servicio)
        $servedDuration = 0;

        if ($currentCall->callStatus?->slug === 'in_progress' && $currentCall->started_at) {
            // Si ya había empezado (estaba en 'in_progress'), calculamos lo que duró
            $servedDuration = $currentCall->started_at->diffInSeconds(now());
        }

        // 2. Calculamos el Turn Around
        // Es la suma de lo que esperó + lo que interactuó (aunque sea 0)
        $turnAroundDuration = $currentCall->waiting_duration + $servedDuration;

        // --- ACTUALIZACIÓN ---
        $currentCall->update([
            'call_status_id' => $statusNoShow->id,
            'ended_at' => now(), // Importante para liberar al cajero
            'served_duration' => $servedDuration,
            'turn_around_duration' => $turnAroundDuration, // <--- Aquí aplicamos tu corrección
        ]);

        $currentCall->ticket->update([
            'call_status_id' => $statusNoShow->id,
        ]);

        // Avisar al Televisor
        CallUpdated::dispatch($currentCall);

        return back();
    }

    /**
     * Wrapper público SOLO para tests / uso interno controlado
     */
    public function testGetInterleavedQueue(array $serviceIds, int $userId)
    {
        return $this->getInterleavedQueue($serviceIds, $userId);
    }

    /**
     * Genera una colección de tickets intercalados (VIP - Normal - VIP - Normal)
     * basándose en el último ticket atendido para decidir quién empieza.
     */
    private function getInterleavedQueue($serviceIds, $userId)
    {
        // 1. Obtenemos TODOS los tickets en espera (ordenados por hora de llegada FIFO)
        $allWaiting = Ticket::whereIn('service_id', $serviceIds)
            ->waiting()
            ->today()
            ->with('service')
            ->orderBy('created_at', 'asc') // Lo más importante: FIFO por llegada real
            ->get();

        // 2. Separamos en dos "baldes"
        // Asumimos: prioridad 1 = VIP, prioridad 0 = Normal
        $vips = $allWaiting->where('priority', 1)->values();
        $normals = $allWaiting->where('priority', 0)->values();

        // 3. Determinar quién va primero (VIP o Normal)
        // Buscamos la última llamada de este usuario para ver qué atendió
        // $lastCall = Call::where('user_id', $userId)->latest()->first();
        $lastCall = Call::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->with('ticket')
            ->orderByDesc('id')
            ->first();

        $nextIsVip = true; // Por defecto arrancamos con VIP si no hay historial

        if ($lastCall) {
            // Si el último fue VIP, ahora le toca a un Normal
            if ($lastCall->ticket && $lastCall->ticket->priority == 1) {
                $nextIsVip = false;
            }
            // Si el último fue Normal (0), $nextIsVip se queda en true
        }

        // 4. El algoritmo de la "Cremallera" (Intercalado)
        $finalQueue = collect([]);

        // Mientras queden tickets en alguno de los dos grupos...
        while ($vips->isNotEmpty() || $normals->isNotEmpty()) {
            if ($nextIsVip) {
                // Toca VIP: Si hay VIPs, sacamos uno. Si no, sacamos Normal (relleno)
                if ($vips->isNotEmpty()) {
                    $finalQueue->push($vips->shift());
                } elseif ($normals->isNotEmpty()) {
                    $finalQueue->push($normals->shift());
                }
            } else {
                // Toca Normal: Si hay Normals, sacamos uno. Si no, sacamos VIP (relleno)
                if ($normals->isNotEmpty()) {
                    $finalQueue->push($normals->shift());
                } elseif ($vips->isNotEmpty()) {
                    $finalQueue->push($vips->shift());
                }
            }

            // Invertimos el turno para la siguiente vuelta
            $nextIsVip = ! $nextIsVip;
        }

        return $finalQueue;
    }

    public function transfer(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Call::class);

        $request->validate([
            'new_service_id' => 'required|exists:services,id',
        ]);

        $user = Auth::user();

        // Estados necesarios
        $statusWaiting = CallStatuse::where('slug', 'waiting')->firstOrFail();
        // Usamos 'transferred' para indicar que esta interacción fue derivada a otro servicio
        $statusTransferred = CallStatuse::where('slug', 'transferred')->firstOrFail();

        // Buscar la llamada actual activa
        $currentCall = Call::where('user_id', $user->id)
            ->whereHas('callStatus', fn ($q) => $q->whereIn('slug', ['calling', 'in_progress']))
            ->latest()
            ->firstOrFail();

        // 1. Cerrar la llamada actual
        $servedDuration = 0;
        // Si ya había iniciado atención, calculamos tiempo parcial
        if ($currentCall->started_at) {
            $servedDuration = $currentCall->started_at->diffInSeconds(now());
        }
        $turnAroundDuration = $currentCall->waiting_duration + $servedDuration;

        $currentCall->update([
            'call_status_id' => $statusTransferred->id, // La marcamos como derivada
            'ended_at' => now(),
            'served_duration' => $servedDuration,
            'turn_around_duration' => $turnAroundDuration,
        ]);

        // 2. Actualizar y Transferir el Ticket
        $ticket = $currentCall->ticket;
        $newTicketNumber = $ticket->ticket_number;

        // Anteponemos 'D' para indicar que es Derivado (sin repetir si ya lo es)
        if (! str_starts_with($newTicketNumber, 'D')) {
            $newTicketNumber = 'D'.$newTicketNumber;
        }

        $ticket->update([
            'service_id' => $request->new_service_id,
            'call_status_id' => $statusWaiting->id, // Vuelve a la cola de espera
            'ticket_number' => $newTicketNumber,
        ]);

        // 3. Eventos
        // Actualizar pantalla (limpiar llamada actual)
        CallUpdated::dispatch($currentCall);

        // Avisar a los DEMÁS que hay un ticket disponible (evita que nosotros recarguemos doble)
        broadcast(new TicketCreated)->toOthers();

        // return back()->with('success', 'Ticket derivado al nuevo servicio correctamente.');
        return back();
    }
}
