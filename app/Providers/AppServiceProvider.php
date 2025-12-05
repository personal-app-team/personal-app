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
use Illuminate\Support\Facades\Gate; // Добавляем эту строку

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

        // Регистрируем политики
        Gate::policy(\App\Models\TraineeRequest::class, \App\Policies\TraineeRequestPolicy::class);
        Gate::policy(\App\Models\Assignment::class, \App\Policies\AssignmentPolicy::class);

        // Очистка логов - ежедневно в 3:00 (используем нашу новую команду)
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Основная очистка старых логов
            $schedule->command('activity:manage cleanup --days=365')
                     ->dailyAt('03:00')
                     ->onOneServer()
                     ->runInBackground()
                     ->appendOutputTo(storage_path('logs/activity-cleanup.log'));
            
            // Оптимизация таблицы - еженедельно в воскресенье
            $schedule->command('activity:manage optimize')
                     ->weeklyOn(0, '04:00') // Воскресенье в 4:00
                     ->onOneServer()
                     ->runInBackground();
            
            // Статистика - ежедневно в 6:00
            $schedule->command('activity:manage stats')
                     ->dailyAt('06:00')
                     ->onOneServer()
                     ->runInBackground()
                     ->appendOutputTo(storage_path('logs/activity-stats.log'));
        });
    }
}
