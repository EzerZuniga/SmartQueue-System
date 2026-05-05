<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\CallStatuse;
use App\Models\CounterAssignment;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DailyCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cierra asignaciones, tickets y llamadas pendientes al final del día.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando limpieza diaria...');

        DB::transaction(function () {
            // 1. Cerrar Asignaciones (CounterAssignment)
            $assignmentsCount = CounterAssignment::whereNull('closed_at')
                ->update(['closed_at' => now()]);
            $this->info("Asignaciones cerradas: $assignmentsCount");

            // Obtener los IDs de los estados
            $waitingStatus = CallStatuse::where('slug', 'waiting')->first();
            $callingStatus = CallStatuse::where('slug', 'calling')->first();
            $inProgressStatus = CallStatuse::where('slug', 'in_progress')->first();

            $completedStatus = CallStatuse::where('slug', 'completed')->first();
            $noShowStatus = CallStatuse::where('slug', 'no_show')->first();

            if (! $waitingStatus || ! $callingStatus || ! $inProgressStatus || ! $completedStatus || ! $noShowStatus) {
                $this->error('Faltan estados en la base de datos (waiting, calling, in_progress, completed, no_show).');

                return;
            }

            // 2. Cerrar Llamadas (Call)

            // a) Calls in_progress -> completed (ended_at = now)
            $callsInProgress = Call::where('call_status_id', $inProgressStatus->id)
                ->whereNull('ended_at')
                ->update([
                    'call_status_id' => $completedStatus->id,
                    'ended_at' => now(),
                ]);
            $this->info("Llamadas 'En Atención' finalizadas: $callsInProgress");

            // b) Calls calling -> no_show (ended_at = now)
            $callsCalling = Call::where('call_status_id', $callingStatus->id)
                ->whereNull('ended_at')
                ->update([
                    'call_status_id' => $noShowStatus->id,
                    'ended_at' => now(),
                ]);
            $this->info("Llamadas 'Llamando' marcadas como No Presentó: $callsCalling");

            // c) Calls waiting -> no_show (called_at, started_at, ended_at = NULL)
            // Nota: Las llamadas en estado 'waiting' generalmente no tienen started_at ni ended_at,
            // pero asegurarse de nullificarlos como regla de negocio.
            $callsWaiting = Call::where('call_status_id', $waitingStatus->id)
                ->update([
                    'call_status_id' => $noShowStatus->id,
                    'called_at' => null,
                    'started_at' => null, // Just to be safe/explicit
                    'ended_at' => null,
                ]);
            $this->info("Llamadas 'En Espera' marcadas como No Presentó (reset times): $callsWaiting");

            // 3. Cerrar Tickets (Ticket)

            // a) Tickets waiting/calling -> no_show
            $ticketsNoShow = Ticket::whereIn('call_status_id', [$waitingStatus->id, $callingStatus->id])
                ->update(['call_status_id' => $noShowStatus->id]);
            $this->info("Tickets 'En Espera/Llamando' marcados como No Presentó: $ticketsNoShow");

            // b) Tickets in_progress -> completed
            $ticketsCompleted = Ticket::where('call_status_id', $inProgressStatus->id)
                ->update(['call_status_id' => $completedStatus->id]);
            $this->info("Tickets 'En Atención' marcados como Finalizado: $ticketsCompleted");
        });

        $this->info('Limpieza diaria completada correctamente.');
    }
}
