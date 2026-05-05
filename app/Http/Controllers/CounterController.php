<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CounterController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Counter::class);
        // 1. Definir opciones válidas y capturar el valor (default: 5)
        $perPage = $request->input('perPage', 5);
        $validPerPage = [5, 10, 20, 50, 100];

        // Si envían algo raro, forzamos a 5
        $perPage = in_array($perPage, $validPerPage) ? $perPage : 5;

        $counters = Counter::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->onEachSide(1)
            ->withQueryString();

        return Inertia::render('counters/Index', [
            'counters' => $counters,
            'filters' => [
                'search' => $request->input('search'),
                'perPage' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Counter::class);

        return Inertia::render('counters/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Counter::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:counters',
            'status' => 'boolean',
        ]);

        Counter::create($validated);

        // Redirección con mensaje Flash (Inertia lo maneja auto si tienes el setup)
        return redirect()->route('counters.index')
            ->with('success', 'Ventanilla creada correctamente.');
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
    public function edit(Counter $counter): Response
    {
        $this->authorize('update', $counter);

        return Inertia::render('counters/Edit', [
            'counter' => $counter,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Counter $counter): RedirectResponse
    {
        $this->authorize('update', $counter);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'boolean',
        ]);

        $counter->update($validated);

        return redirect()->route('counters.index')
            ->with('success', 'Ventanilla actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Counter $counter): RedirectResponse
    {
        $this->authorize('delete', $counter);
        $counter->delete();

        return redirect()->route('counters.index')
            ->with('success', 'Ventanilla eliminada.');
    }
}
