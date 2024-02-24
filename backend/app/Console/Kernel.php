<?php

namespace App\Console;

use App\Console\Commands\Daily;
use App\Console\Commands\Refresh;
use App\Console\Commands\Weekly;
use App\Models\Setting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Schema;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
      if(app()->runningUnitTests()) {
        return;
      }

      if (Schema::hasTable('settings')) {
        $settings = Setting::first();

        if ($settings->refresh_automatically) {
          $schedule->command(Refresh::class)->dailyAt('06:00');
        }

        if ($settings->daily_reminder) {
          $schedule->command(Daily::class)->dailyAt(config('app.DAILY_REMINDER_TIME'));
        }

        if ($settings->weekly_reminder) {
          $schedule->command(Weekly::class)->sundays()->at(config('app.WEEKLY_REMINDER_TIME'));
        }
      }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
