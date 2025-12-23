<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\WorkRequest;
use App\Observers\WorkRequestObserver;
use App\Models\Assignment;
use App\Observers\AssignmentObserver;
use App\Models\TraineeRequest;
use App\Observers\TraineeRequestObserver;
use Illuminate\Console\Scheduling\Schedule;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        WorkRequest::observe(WorkRequestObserver::class);
        Assignment::observe(AssignmentObserver::class);
        TraineeRequest::observe(TraineeRequestObserver::class);

        // УБИРАЕМ отсюда регистрацию политик
        // Они теперь в AuthServiceProvider

        // Очистка логов - ежедневно в 3:00
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('activity:manage cleanup --days=365')
                     ->dailyAt('03:00')
                     ->onOneServer()
                     ->runInBackground()
                     ->appendOutputTo(storage_path('logs/activity-cleanup.log'));
            
            $schedule->command('activity:manage optimize')
                     ->weeklyOn(0, '04:00')
                     ->onOneServer()
                     ->runInBackground();
            
            $schedule->command('activity:manage stats')
                     ->dailyAt('06:00')
                     ->onOneServer()
                     ->runInBackground()
                     ->appendOutputTo(storage_path('logs/activity-stats.log'));
        });
    }
}
