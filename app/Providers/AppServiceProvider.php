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
        TraineeRequest::observe(TraineeRequestObserver::class); // РАСКОММЕНТИРОВАТЬ
        
        // Регистрируем политики
        \Illuminate\Support\Facades\Gate::policy(\App\Models\TraineeRequest::class, \App\Policies\TraineeRequestPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Assignment::class, \App\Policies\AssignmentPolicy::class);

        // Добавляем расписание для очистки логов
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('activitylog:clean')->dailyAt('03:00');
        });
    }
}
