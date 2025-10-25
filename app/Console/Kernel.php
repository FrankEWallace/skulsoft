<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('test-cron-job')->everyMinute();
        $schedule->command('media:prune')->dailyAt('02:00');
        $schedule->command('activitylog:clean')->dailyAt('01:30');
        $schedule->command('backup:run')->daily();
        $schedule->command('backup:clean')->dailyAt('01:00');
        $schedule->command('ccavenue:status')->everyFiveMinutes();
        $schedule->command('billdesk:status')->everyFiveMinutes();
        $schedule->command('student:update-service-allocation')->dailyAt('01:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
