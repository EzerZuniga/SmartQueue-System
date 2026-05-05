<?php

namespace App\Http\Controllers;

use App\Exports\CallsExport;
use App\Exports\PerformanceExport;
use App\Exports\TicketsExport;
use App\Models\Call;
use App\Models\CallStatuse;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('reports/Index');
    }

    public function tickets(Request $request)
    {
        $query = Ticket::with(['service', 'latestCall.counter', 'callStatuse']);

        if ($request->has('start_date') && $request->has('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        $tickets = $query->latest()->paginate(15);

        return response()->json($tickets);
    }

    public function calls(Request $request)
    {
        $query = Call::with(['ticket', 'user', 'counter', 'service', 'callStatus']);

        if ($request->has('start_date') && $request->has('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        $calls = $query->latest()->paginate(15);

        return response()->json($calls);
    }

    public function exportTickets(Request $request)
    {
        return Excel::download(new TicketsExport($request->all()), 'tickets.xlsx');
    }

    public function exportCalls(Request $request)
    {
        return Excel::download(new CallsExport($request->all()), 'calls.xlsx');
    }

    public function performance(Request $request)
    {
        $completedStatusId = CallStatuse::idBySlug('completed');

        $query = Call::with('user')
            ->select('user_id', DB::raw('count(*) as total_calls'), DB::raw('avg(served_duration) as avg_served_time'))
            ->when($completedStatusId, fn ($q) => $q->where('call_status_id', $completedStatusId))
            ->groupBy('user_id');

        if ($request->has('start_date') && $request->has('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        $performance = $query->get();

        return response()->json($performance);
    }

    public function exportPerformance(Request $request)
    {
        return Excel::download(new PerformanceExport($request->all()), 'performance.xlsx');
    }
}
