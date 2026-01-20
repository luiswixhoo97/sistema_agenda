<?php

namespace App\Console\Commands;

use App\Services\NotificacionService;
use Illuminate\Console\Command;

class EnviarRecordatoriosPush extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'citas:recordatorios-push';

    /**
     * The console command description.
     */
    protected $description = 'Envía recordatorios push 10 minutos antes de las citas';

    /**
     * Execute the console command.
     */
    public function handle(NotificacionService $notificacionService): int
    {
        $this->info('Verificando citas próximas para recordatorios push...');
        
        $notificacionService->enviarRecordatoriosPush();
        
        $this->info('Recordatorios procesados.');
        
        return Command::SUCCESS;
    }
}
