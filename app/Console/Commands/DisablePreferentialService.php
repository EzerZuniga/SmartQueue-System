<?php

namespace App\Console\Commands;

use App\Models\Service;
use Illuminate\Console\Command;

class DisablePreferentialService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:disable-preferential-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deshabilita el servicio preferencial (Prefijo P).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Service::where('prefix', 'P')->update(['status' => false]);

        if ($count > 0) {
            $this->info('Servicio preferencial (P) deshabilitado correctamente.');
        } else {
            $this->warn("No se encontró ningún servicio con el prefijo 'P'.");
        }
    }
}
