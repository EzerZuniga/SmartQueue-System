<?php

namespace App\Exports;

use App\Models\Call;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CallsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function collection()
    {
        $query = Call::with(['ticket', 'user', 'counter', 'service', 'callStatus']);

        if (isset($this->params['start_date']) && isset($this->params['end_date'])) {
            $start = Carbon::parse($this->params['start_date'])->startOfDay();
            $end = Carbon::parse($this->params['end_date'])->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Operador',
            'Ventanilla',
            'Servicio',
            'Ticket',
            'Llamada',      // Nuevo
            'Inicio',
            'Fin',
            'Espera',       // Nuevo
            'Atención',     // Renombrado
            'Estado Final', // Renombrado
        ];
    }

    public function map($call): array
    {
        // Calcular duración de atención
        $servedDurationStr = 'En curso';
        if ($call->started_at && $call->ended_at) {
            $diffMs = Carbon::parse($call->started_at)->diffInMilliseconds(Carbon::parse($call->ended_at));
            $servedDurationStr = $this->formatMs($diffMs);
        } elseif (! $call->started_at) {
            $servedDurationStr = '0m 0s';
        }

        // Formatear espera
        // waiting_duration está en segundos según la migración
        $waitingDurationStr = $this->formatSeconds($call->waiting_duration ?? 0);

        return [
            $call->user->name ?? '-',
            $call->counter->name ?? '-',
            $call->service->name ?? '-',
            $call->ticket->ticket_number,
            $call->called_at ? Carbon::parse($call->called_at)->format('g:i A') : '-',
            $call->started_at ? Carbon::parse($call->started_at)->format('g:i A') : '-',
            $call->ended_at ? Carbon::parse($call->ended_at)->format('g:i A') : '-',
            $waitingDurationStr,
            $servedDurationStr,
            $call->callStatus ? $call->callStatus->name : $call->status,
        ];
    }

    private function formatSeconds($seconds)
    {
        $mins = floor($seconds / 60);
        $secs = $seconds % 60;

        return "{$mins}m {$secs}s";
    }

    private function formatMs($ms)
    {
        $seconds = floor($ms / 1000);

        return $this->formatSeconds($seconds);
    }
}
