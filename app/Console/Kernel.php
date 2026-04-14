<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Limpia backups antiguos cada 15 dias
        $schedule->command('backup:clean')
            ->daily()
            ->at('01:30');

        // Genera backup y envia por email
        $schedule->command('backup:email')
            ->daily()
            ->at('10:35')
            ->onFailure(function () {
                \Log::error('Backup por email fallo');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
