<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\CounterAssignment;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\OperationalAlert;
use Illuminate\Console\Command;

class CheckOperationalAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-operational-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica métricas operativas y genera alertas si es necesario (Espera Larga, Cuellos de Botella).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->checkLongWaitAlerts();
        $this->checkBottleneckAlerts();
    }

    private function checkLongWaitAlerts()
    {
        // 1. Buscamos llamadas activas con más de 45 mins
        $longCalls = Call::whereNull('ended_at')
            ->where('started_at', '<', now()->subMinutes(5))
            ->with(['counter', 'ticket'])
            ->get();

        // $admins = \App\Models\User::role('admin')->get(); // Asume Spatie o similar, si no, ajustar a User::where('is_admin', true) o similar.
        // Si no hay roles, usar User::all() o un usuario específico.
        // Fallback si no hay roles: User::limit(5)->get() para pruebas o todos.
        if (User::count() > 0 && ! method_exists(User::class, 'scopeRole')) {
            $admins = User::all(); // Notificar a todos si no hay roles
        }

        foreach ($longCalls as $call) {
            $counterName = $call->counter->name ?? 'Ventanilla';
            $ticketNumber = $call->ticket->ticket_number ?? '???';
            $duration = $call->started_at->diffInMinutes(now());

            $message = "⚠️ La $counterName lleva $duration minutos atendiendo el ticket $ticketNumber.";
            $sourceId = 'long_wait_call_'.$call->id;

            $this->notifyAdmins($admins, $message, 'warning', $sourceId);
        }
    }

    private function checkBottleneckAlerts()
    {
        // 2. Buscamos servicios con mucha demanda (>15) y pocos cajeros (<2) activos (simplificado)

        // Contamos tickets en espera por servicio
        $waitingByService = Ticket::waiting() // Scope defined in Ticket
            ->selectRaw('service_id, count(*) as count')
            ->groupBy('service_id')
            ->get();

        $admins = User::all(); // Ajustar selectores de usuarios

        foreach ($waitingByService as $stat) {
            if ($stat->count > 5) {
                // Verificar cuántas asignaciones activas cubren este servicio
                $activeAssignments = CounterAssignment::whereNull('closed_at')
                    ->whereHas('services', function ($q) use ($stat) {
                        $q->where('services.id', $stat->service_id);
                    })
                    ->count();

                if ($activeAssignments < 2) {
                    $serviceName = Service::find($stat->service_id)->name ?? 'Servicio';
                    $message = "⚠️ Hay {$stat->count} personas esperando para '$serviceName' y solo $activeAssignments cajero(s) activo(s).";
                    $sourceId = 'bottleneck_service_'.$stat->service_id;

                    $this->notifyAdmins($admins, $message, 'danger', $sourceId);
                }
            }
        }
    }

    private function notifyAdmins($admins, $message, $level, $sourceId)
    {
        foreach ($admins as $admin) {
            // Anti-spam: Verificar si ya tiene una notificación similar no leída reciente (30 min)
            $exists = $admin->unreadNotifications()
                ->where('type', OperationalAlert::class)
                ->where('data->source_id', $sourceId)
                ->where('created_at', '>', now()->subMinutes(30))
                ->exists();

            if (! $exists) {
                $admin->notify(new OperationalAlert($message, $level, $sourceId));
            }
        }
    }
}
