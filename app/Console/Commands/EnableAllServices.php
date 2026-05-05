<?php

namespace App\Console\Commands;

use App\Models\Service;
use Illuminate\Console\Command;

class EnableAllServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:enable-all-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Habilita todos los servicios.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Service::query()->update(['status' => true]);

        $this->info("Se han habilitado todos los servicios. Total actualizados: {$count}");
    }
}
