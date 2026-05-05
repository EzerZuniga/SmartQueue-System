<?php

namespace App\Exports;

use App\Models\Call;
use App\Models\CallStatuse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PerformanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function collection()
    {
        $completedStatusId = CallStatuse::idBySlug('completed');

        $query = Call::with('user')
            ->select('user_id', DB::raw('count(*) as total_calls'), DB::raw('avg(served_duration) as avg_served_time'))
            ->when($completedStatusId, fn ($q) => $q->where('call_status_id', $completedStatusId))
            ->groupBy('user_id');

        if (isset($this->params['start_date']) && isset($this->params['end_date'])) {
            $start = Carbon::parse($this->params['start_date'])->startOfDay();
            $end = Carbon::parse($this->params['end_date'])->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Operador',
            'Total Atendidos',
            'Tiempo Promedio Atención',
        ];
    }

    public function map($row): array
    {
        return [
            $row->user ? $row->user->name : 'Desconocido',
            $row->total_calls,
            gmdate('H:i:s', $row->avg_served_time),
        ];
    }
}
