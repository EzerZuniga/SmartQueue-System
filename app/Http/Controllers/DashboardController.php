<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\CounterAssignment;
use App\Models\Ticket;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'initialStats' => $this->getStats(),
        ]);
    }

    public function stats()
    {
        return response()->json($this->getStats());
    }

    private function getStats()
    {
        $today = today();

        // 1. KPIs

        // En Espera: Tickets con estado 'waiting' (Hoy)
        $waitingCount = Ticket::waiting()->today()->count();

        // Tiempo Promedio de Espera (Hoy)
        // avg('waiting_duration') en segundos
        $avgWaitTimeSeconds = Call::today()->avg('waiting_duration') ?? 0;
        $avgWaitTimeText = gmdate('i:s', (int) $avgWaitTimeSeconds); // Min:Seg

        // Ventanillas Activas
        $activeCountersCount = CounterAssignment::whereNull('closed_at')->count();

        // Tasa de Abandono (Hoy)
        // Calls no_show / Total Calls
        $totalCallsToday = Call::today()->count();
        $noShowCallsToday = Call::today()->noShow()->count();

        $abandonmentRate = $totalCallsToday > 0
            ? round(($noShowCallsToday / $totalCallsToday) * 100, 1)
            : 0;

        // 2. Gráficos

        // Curva de Demanda (Tickets por hora) - SOLO HOY (6:00 - 22:00)
        $hours = collect(range(6, 22));
        $todayData = Ticket::today()
            ->whereTime('created_at', '>=', '06:00:00')
            ->whereTime('created_at', '<=', '22:00:00')
            ->get(['created_at'])
            ->groupBy(fn ($ticket) => $ticket->created_at->hour)
            ->map(fn ($tickets) => $tickets->count());
        $demandChart = $hours->map(function ($hour) use ($todayData) {
            $date = today()->setHour($hour)->setMinute(0)->setSecond(0)->format('Y-m-d\TH:i:s');

            return [
                'date' => $date,
                'tickets' => $todayData->get($hour, 0),
            ];
        })->values();

        // Distribución por Servicio
        $serviceDistribution = Ticket::whereDate('tickets.created_at', $today)
            ->join('services', 'tickets.service_id', '=', 'services.id')
            ->selectRaw('services.name as name, count(*) as count')
            ->groupBy('services.name')
            ->get();

        // 3. Estado de Operadores (Grid)
        // Traemos asignaciones abiertas con su contador y usuario
        // Y tratamos de buscar si tienen una llamada activa (ended_at = null)
        $openAssignments = CounterAssignment::whereNull('closed_at')
            ->with(['user', 'counter'])
            ->get();

        $activeCalls = Call::whereNull('ended_at')
            ->whereIn('counter_id', $openAssignments->pluck('counter_id')->unique())
            ->whereIn('user_id', $openAssignments->pluck('user_id')->unique())
            ->with('ticket')
            ->get()
            ->keyBy(fn ($call) => $call->counter_id.'-'.$call->user_id);

        $activeAssignments = $openAssignments
            ->map(function ($assignment) use ($activeCalls) {
                $activeCall = $activeCalls->get($assignment->counter_id.'-'.$assignment->user_id);

                $status = 'active'; // Por defecto 'active' (conectado pero quizás esperando)
                // Lógica de estado simplificada:
                // Si tiene llamada activa -> 'serving' (Atendiendo)
                // Si no -> 'waiting' (En espera de ticket)
                // 'paused' -> Si implementas pausas

                if ($activeCall) {
                    $status = 'serving';
                } else {
                    $status = 'waiting';
                }

                return [
                    'id' => $assignment->id,
                    'operator_name' => $assignment->user->name,
                    'counter_name' => $assignment->counter->name,
                    'status' => $status, // serving, waiting
                    'current_ticket' => $activeCall ? $activeCall->ticket->ticket_number : null,
                    'call_started_at' => $activeCall ? $activeCall->started_at : null,
                ];
            });

        return [
            'kpis' => [
                'waiting_count' => $waitingCount,
                'avg_wait_time' => $avgWaitTimeText,
                'active_counters' => $activeCountersCount,
                'abandonment_rate' => $abandonmentRate.'%',
            ],
            'charts' => [
                'demand' => $demandChart,
                'distribution' => $serviceDistribution,
            ],
            'operators' => $activeAssignments,
        ];
    }
}
