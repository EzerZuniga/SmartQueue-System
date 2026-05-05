<?php

namespace App\Http\Controllers;

use App\Events\TicketCreated;
use App\Models\CallStatuse;
use App\Models\CounterAssignment;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Ticket;
use App\Notifications\NewTicketAlert;
use App\Services\ReniecService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    protected ReniecService $reniecService;

    public function __construct(ReniecService $reniecService)
    {
        $this->reniecService = $reniecService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(string $token): Response
    {
        $settings = Setting::first();
        if (! $settings || ! $settings->kiosk_token || ! hash_equals($settings->kiosk_token, $token)) {
            abort(404);
        }

        // Solo servicios activos para que el cliente elija
        $services = Service::where('status', true)
            ->get(['id', 'name', 'prefix', 'start_number', 'ask_document']);

        // Generamos la URL completa si existe el logo
        $logoUrl = ($settings && $settings->logo_path)
            ? asset('storage/'.$settings->logo_path)
            : null;

        return Inertia::render('tickets/Create', [
            'services' => $services,
            'logoUrl' => $logoUrl,
            'kioskToken' => $token,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        $settings = Setting::firstOrFail();
        if (! $settings->kiosk_token || ! hash_equals($settings->kiosk_token, $token)) {
            abort(403, 'Token de kiosko inválido.');
        }

        $validatedData = $this->validateTicketRequest($request, $settings);

        $ticket = DB::transaction(function () use ($validatedData) {
            return $this->createTicket($validatedData);
        });

        TicketCreated::dispatch();

        // --- Notificar Operadores ---
        // Buscamos asignaciones activas que cubran este servicio
        $activeAssignments = CounterAssignment::whereNull('closed_at')
            ->whereHas('services', function ($q) use ($ticket) {
                $q->where('services.id', $ticket->service_id);
            })
            ->with('user')
            ->get();

        $usersToNotify = $activeAssignments->pluck('user')->unique('id');

        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new NewTicketAlert($ticket));
        }
        // ----------------------------

        $printData = $this->preparePrintData($ticket, $settings);

        return back()->with('ticket_created', $printData);
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Valida la solicitud aplicando reglas dinámicas del servicio.
     */
    private function validateTicketRequest(Request $request, Setting $setting): array
    {
        // Validación base
        $request->validate([
            'service_id' => [
                'required',
                Rule::exists('services', 'id')->where(fn ($query) => $query->where('status', true)),
            ],
            'priority' => 'sometimes|integer|in:0,1',
        ]);

        $service = Service::findOrFail($request->service_id);
        $reniecService = $this->reniecService; // Scope local para closure

        if (! $request->filled('client_document')) {
            $request->merge(['client_document' => '00000000']);
        }

        // Validación de Documento (Kiosko)
        $rules['client_document'] = [
            'required',
            function ($attribute, $value, $fail) use ($reniecService, $setting) {
                // 1. Verificar si coincide con el código de validación (maestro)
                if ($setting && $setting->kiosk_code && $value === $setting->kiosk_code) {
                    return;
                }

                // 2. Verificar que sea numérico
                if (! is_numeric($value)) {
                    $fail('El documento debe contener solo números.');

                    return;
                }

                // 3. Verificar longitud (8 para DNI, 11 para RUC/Otros)
                $length = strlen($value);
                if ($length !== 8 && $length !== 11) {
                    $fail('El documento debe tener 8 u 11 dígitos.');

                    return;
                }

                // 4. Rate Limiting (Cooldown)
                // IMPORTANTE: Excluimos '00000000' para que los tickets anónimos no se bloqueen entre sí.
                if ($value !== '00000000') {
                    $cooldownMinutes = $setting->ticket_cooldown_minutes ?? 10;

                    $lastTicket = Ticket::where('client_document', $value)
                        ->latest()
                        ->first();

                    if ($lastTicket) {
                        $releaseTime = $lastTicket->created_at->copy()->addMinutes($cooldownMinutes);

                        if (now()->lt($releaseTime)) {
                            $fail('Ya tiene un turno activo.');
                        }
                    }
                }

                // 5. Validación con RENIEC (Solo DNI reales)
                // Ignoramos anónimos y códigos maestros (ya filtrados arriba)
                if ($value !== '00000000' && ($setting && $value !== $setting->kiosk_code)) {
                    // Usamos la instancia inyectada que pasamos por 'use'
                    $nombre = $reniecService->search($value);

                    if (! $nombre) {
                        $fail('Fallo al validar el documento ingresado');

                        return;
                    }

                    // Guardamos el nombre en el request para usarlo luego
                    request()->merge(['validated_client_name' => $nombre]);
                }
            },
        ];

        $rules['client_name'] = [
            $service->ask_name && $service->name_required ? 'required' : 'nullable',
            'string',
            'max:255',
        ];

        $rules['client_email'] = [
            $service->ask_email ? 'required' : 'nullable',
            'email',
            'max:255',
        ];

        $rules['client_phone'] = [
            $service->ask_phone ? 'required' : 'nullable',
            'string',
            'max:30',
        ];

        // Retornamos los datos validados + el objeto Service para no buscarlo de nuevo
        $data = $request->validate($rules);
        $data['service'] = $service;
        $data['priority'] = $request->input('priority', 0);

        // Persistimos nombre manual si fue enviado; si no, aplicamos fallback por documento.
        $submittedName = $request->input('client_name');
        if (is_string($submittedName) && trim($submittedName) !== '') {
            $data['client_name'] = $submittedName;
        } elseif ($request->client_document === '00000000') {
            $data['client_name'] = null;
        } elseif ($request->client_document === $setting->kiosk_code) {
            $data['client_name'] = null;
        } else {
            $data['client_name'] = $request->input('validated_client_name');
        }

        return $data;
    }

    /**
     * Lógica central de creación del ticket.
     * Calcula correlativos y posiciones "Cremallera".
     */
    private function createTicket(array $data): Ticket
    {
        $service = $data['service']; // Recuperado de la validación

        // A. Determinar Prioridad Final (Considerando si el servicio YA es VIP por defecto)
        $isPreferentialRequest = $data['priority'] == 1;
        $isServiceVipByDefault = strtoupper($service->prefix) === 'P';

        $finalPriority = ($isPreferentialRequest || $isServiceVipByDefault) ? 1 : 0;

        // B. Calcular Número Visual (Ej: A-005)
        // Bloqueamos para lectura segura del contador diario
        // Contamos todos los tickets que pertenezcan originalmente a este prefijo (incluyendo derivados 'D' y preferenciales 'P')
        $prefixes = [
            $service->prefix,
            'P'.$service->prefix,
            'D'.$service->prefix,
            'DP'.$service->prefix,
            'PD'.$service->prefix,
        ];

        $countToday = Ticket::whereDate('created_at', today())
            ->where(function ($query) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $query->orWhere('ticket_number', 'like', $prefix.'-%');
                }
            })
            ->lockForUpdate()
            ->count();

        $nextNumber = $service->start_number + $countToday;

        // Prefijo: Si es VIP forzado, anteponemos 'P' (Ej: "PCV"), salvo que ya sea "P" nativo
        $prefix = $service->prefix;
        if ($isPreferentialRequest && ! $isServiceVipByDefault) {
            $prefix = 'P'.$prefix;
        }
        $ticketCode = $prefix.'-'.str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // C. Calcular Posición Matemática "Cremallera" (Zipper)
        // VIPs = Impares (1, 3, 5...), Normales = Pares (2, 4, 6...)
        $countInGroup = Ticket::where('service_id', $service->id)
            ->whereDate('created_at', today())
            ->where('priority', $finalPriority)
            ->count();

        $myRank = $countInGroup + 1;
        $zipperPosition = ($finalPriority === 1) ? ($myRank * 2) - 1 : ($myRank * 2);

        // D. Guardar
        $statusWaiting = CallStatuse::where('slug', 'waiting')->value('id');

        return Ticket::create([
            'service_id' => $service->id,
            'ticket_number' => $ticketCode,
            'number' => $nextNumber,
            'position' => $zipperPosition, // <--- La magia del ordenamiento
            'priority' => $finalPriority,
            'call_status_id' => $statusWaiting,
            'client_document' => $data['client_document'],
            'client_name' => $data['client_name'], // Usamos el nombre validado
            'client_email' => $data['client_email'] ?? null,
            'client_phone' => $data['client_phone'] ?? null,
        ]);
    }

    /**
     * Prepara el array de datos que necesita el frontend para imprimir el ticket.
     */
    private function preparePrintData(Ticket $ticket, Setting $settings): array
    {
        // Calcular gente esperando antes que yo (para mostrar "Hay X personas delante")
        // Comparamos usando la 'position' matemática global que acabamos de generar
        $waitingAhead = Ticket::waiting()
            ->today()
            ->where('service_id', $ticket->service_id)
            ->where('id', '!=', $ticket->id)
            ->where('position', '<', $ticket->position)
            ->count();

        return [
            'ticket_number' => $ticket->ticket_number,
            'service_name' => $ticket->service->name,
            'created_at' => $ticket->created_at->format('d/m/Y H:i'),
            'waiting_count' => $waitingAhead,
            'settings' => [
                'name' => $settings->name,
                'address' => $settings->address,
                'email' => $settings->email,
                'phone' => $settings->phone,
                'print' => $settings->print_preview_enabled,
                'footer' => $settings->footer_text,
            ],
        ];
    }
}
