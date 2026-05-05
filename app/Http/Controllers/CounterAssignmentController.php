<?php

namespace App\Http\Controllers;

use App\Events\DashboardStatUpdated;
use App\Models\Counter;
use App\Models\CounterAssignment;
use App\Models\Service;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CounterAssignmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(): Response|RedirectResponse
    {
        $this->authorize('viewAny', CounterAssignment::class);
        $user = Auth::user();

        $assignment = $user->currentAssignment()
            ->with(['counter', 'services'])
            ->first();

        // Si NO está trabajando, lo mandamos a elegir ventanilla
        if (! $assignment) {
            return redirect()->route('counterAssignments.create');
        }

        return Inertia::render('assignments/Index', [
            'assignment' => [
                'id' => $assignment->id,
                'counter' => $assignment->counter->name,
                'opened_at' => $assignment->time_open, // Hora inicio
                'services' => $assignment->services->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ]),
            ],
        ]);
    }

    /**
     * Muestra la pantalla para elegir ventanilla
     */
    public function create(): RedirectResponse|Response
    {
        $this->authorize('create', CounterAssignment::class);
        $assignment = Auth::user()->currentAssignment()
            ->with(['counter', 'services'])
            ->first();

        // Si está trabajando, lo mandamos a visualizar su ventanilla
        if ($assignment) {
            return redirect()->route('counterAssignments.index');
        }

        $counters = Counter::with(['activeAssignments.user'])
            ->where('status', true)
            ->get()
            ->map(function ($counter) {
                return [
                    'id' => $counter->id,
                    'name' => $counter->name,
                    // Devolvemos un array con los nombres de quienes están ahí
                    'current_operators' => $counter->activeAssignments->map(fn ($a) => [
                        'name' => $a->user->name,
                        'initials' => substr($a->user->name, 0, 2),
                    ]),
                ];
            });

        $services = Service::where('status', true)->get(['id', 'name']);

        return Inertia::render('assignments/Create', [
            'counters' => $counters,
            'services' => $services,
        ]);
    }

    /**
     * El empleado elige una ventanilla.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CounterAssignment::class);
        $request->validate([
            'counter_id' => 'required|exists:counters,id',
            'services' => 'required|array|min:1', // Debe elegir al menos 1
            'services.*' => 'exists:services,id',
        ]);

        // Doble validación: Verificar que la ventanilla NO esté ocupada por otro justo ahora
        /* $isOccupied = CounterAssignment::where('counter_id', $request->counter_id)
            ->whereNull('closed_at')
            ->exists();

        if ($isOccupied) {
            return back()->withErrors(['counter_id' => 'Esta ventanilla acaba de ser ocupada por otro compañero.']);
        }*/

        // Validamos que EL MISMO usuario no se asigne dos veces a la misma caja al mismo tiempo
        $alreadyAssigned = CounterAssignment::where('user_id', Auth::id())
            ->whereNull('closed_at')
            ->exists();

        if ($alreadyAssigned) {
            return redirect()->route('counterAssignments.index'); // Ya estás ahí, solo redirige
        }

        // 1. Crear la Asignación (Sesión de Caja)
        $assignment = CounterAssignment::create([
            'user_id' => Auth::id(),
            'counter_id' => $request->counter_id,
            'opened_at' => now(),
        ]);

        // 2. Guardar los servicios que va a atender en esta sesión
        $assignment->services()->attach($request->services);

        DashboardStatUpdated::dispatch();

        return redirect()->route('counterAssignments.index')->with('success', 'Asignación creada');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * El empleado cierra su caja (se va a almorzar o termina turno).
     */
    public function destroy(string $id): RedirectResponse
    {
        $user = Auth::user();
        $assignment = $user->currentAssignment;

        $this->authorize('delete', $assignment);

        // Validamos que exista y que coincida con el ID enviado (seguridad extra)
        if ($assignment && $assignment->id == $id) {
            $assignment->update([
                'closed_at' => now(),
            ]);

            // Opcional: Desvincular servicios si quisieras limpiar la tabla pivote,
            // pero mejor dejarlos por histórico.
        }

        DashboardStatUpdated::dispatch();

        return redirect()->route('counterAssignments.create')
            ->with('success', 'Turno finalizado correctamente.');
    }
}
