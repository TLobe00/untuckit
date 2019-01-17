<?php

namespace App\Console;

use App\Console\Commands\CheckOrderFulfillments;
use App\Console\Commands\RunCustomPDP;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        RunCustomPDP::class,
        CheckOrderFulfillments::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
         $schedule->command('untuckit:runcustompdp')
                  ->timezone('Asia/Bangkok')
                  ->dailyAt('06:00');

        # Check for missing order fulfillments every hour
        $schedule->command('orders:update-fulfillments')->hourly();
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
