<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //rate
        for ($i = 0; $i < 60; $i = $i + 3) {
            $schedule->command('rate:batch',['--delay'=> $i])->everyMinute();
        }
        for ($i = 0; $i < 60; $i = $i + 10) {
            $schedule->command('btc:monitoring',['--delay'=> $i])->everyMinute();
            $schedule->command('eth:monitoring',['--delay'=> $i])->everyMinute();
            $schedule->command('xrp:monitoring',['--delay'=> $i])->everyMinute();
        }
        $schedule->command('balance:batch')->hourly();

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
