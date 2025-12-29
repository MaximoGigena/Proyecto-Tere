<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Actualizar edades todos los días a las 3:00 AM
        $schedule->command('mascotas:actualizar-edades')
                 ->hourly()       // Cada hora
                 ->timezone('America/Argentina/Buenos_Aires') // Ajusta tu timezone
                 ->appendOutputTo(storage_path('logs/edades-mascotas.log'));

        // Para testing, puedes usar:
        // ->everyMinute()  // Cada minuto
        // ->hourly()       // Cada hora
        // ->daily()        // Cada día a medianoche
        $schedule->command('sanciones:verificar-expiracion')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}