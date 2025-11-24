<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\WorkRequest;
use App\Observers\WorkRequestObserver;
use App\Models\Assignment;
use App\Observers\AssignmentObserver;

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
        
        // Регистрируем политику для TraineeRequest стандартным способом
        \Illuminate\Support\Facades\Gate::policy(\App\Models\TraineeRequest::class, \App\Policies\TraineeRequestPolicy::class);
    }
}
