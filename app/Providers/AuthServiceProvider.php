<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        $this->registerPolicies();

        // Отключаем автоматическое определение политик
        Gate::guessPolicyNamesUsing(fn () => null);

        // Глобальные правила - администратор может всё
        Gate::before(fn ($user) => $user->hasRole('admin'));

        // Кастомные gates
        Gate::define('confirm_assignment', fn ($user, $assignment) => 
            $user->hasRole('executor') &&
            $user->id === $assignment->user_id &&
            $assignment->status === 'pending'
        );

        Gate::define('reject_assignment', fn ($user, $assignment) => 
            $user->hasRole('executor') &&
            $user->id === $assignment->user_id &&
            $assignment->status === 'pending'
        );

        Gate::define('create_shift', fn ($user, $assignment) => 
            $user->hasRole('executor') &&
            $user->id === $assignment->user_id &&
            $assignment->status === 'confirmed'
        );

        Gate::define('take_work_request', fn ($user, $workRequest) => 
            $user->hasRole('dispatcher') &&
            $workRequest->status === 'published'
        );

        Gate::define('create_brigadier_schedule', fn ($user) => 
            $user->hasAnyRole(['initiator', 'dispatcher', 'admin']) &&
            $user->can('create_assignment')
        );

        Gate::define('create_work_request_assignment', fn ($user, $workRequest) => 
            $user->hasRole('dispatcher') &&
            $workRequest->dispatcher_id === $user->id &&
            $user->can('create_assignment')
        );

        Gate::define('create_mass_personnel_assignment', fn ($user) => 
            $user->hasAnyRole(['dispatcher', 'contractor_admin', 'contractor_dispatcher', 'admin']) &&
            $user->can('create_assignment')
        );

        Gate::define('confirm_mass_personnel_assignment', function ($user, $assignment) {
            if ($assignment->assignment_type !== 'mass_personnel') return false;
            if (!$assignment->workRequest || !$user->contractor_id) return false;
            return $assignment->workRequest->contractor_id === $user->contractor_id &&
                $user->hasAnyRole(['contractor_admin', 'contractor_dispatcher']);
        });
    }
}
