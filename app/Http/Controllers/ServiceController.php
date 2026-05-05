<?php

namespace App\Http\Controllers;

use App\Events\ServicesUpdated;
use App\Models\Service;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Service::class);
        $perPage = $request->input('perPage', 5);
        $perPage = in_array($perPage, [5, 10, 20, 50]) ? $perPage : 5;

        $services = Service::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('prefix', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('services/Index', [
            'services' => $services,
            'filters' => [
                'search' => $request->input('search'),
                'perPage' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Service::class);

        return Inertia::render('services/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Service::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:5|unique:services', // Ej: "A", "PRE"
            'start_number' => 'required|integer|min:0',
            'status' => 'boolean',
            // Reglas de Kiosco
            'ask_document' => 'boolean',
            'ask_name' => 'boolean',
            'name_required' => 'boolean',
            'ask_email' => 'boolean',
            'ask_phone' => 'boolean',
        ]);

        Service::create($validated);

        ServicesUpdated::dispatch();

        return redirect()->route('services.index')
            ->with('success', 'Servicio creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    public function edit(Service $service): Response
    {
        $this->authorize('update', $service);

        return Inertia::render('services/Edit', [
            'service' => $service,
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:5',
            'start_number' => 'required|integer|min:0',
            'status' => 'boolean',
            'ask_document' => 'boolean',
            'ask_name' => 'boolean',
            'name_required' => 'boolean',
            'ask_email' => 'boolean',
            'ask_phone' => 'boolean',
        ]);

        $service->update($validated);

        ServicesUpdated::dispatch();

        return redirect()->route('services.index')
            ->with('success', 'Servicio actualizado correctamente.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('delete', $service);
        $service->delete();

        return redirect()->route('services.index')
            ->with('success', 'Servicio eliminado correctamente.');
    }
}
