<?php

namespace App\Exports;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TicketsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function collection()
    {
        $query = Ticket::with(['service', 'latestCall.counter', 'callStatuse']);

        if (isset($this->params['start_date']) && isset($this->params['end_date'])) {
            $start = Carbon::parse($this->params['start_date'])->startOfDay();
            $end = Carbon::parse($this->params['end_date'])->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        Log::info($query->latest()->get());

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Ticket #',
            'Servicio',
            'Estado',
            'Creado',
            'Documento',
            'Cliente',
            'Ventanilla',
        ];
    }

    public function map($ticket): array
    {
        return [
            $ticket->ticket_number,
            $ticket->service->name,
            $ticket->callStatuse?->name ?? 'N/A',
            $ticket->created_at ? Carbon::parse($ticket->created_at)->format('d/m/Y g:i A') : '-',
            $ticket->client_document ?? '-',
            $ticket->client_name ?? '-',
            $ticket->latestCall?->counter ? $ticket->latestCall->counter->name : 'N/A',
        ];
    }
}
