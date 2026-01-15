<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Assignment;
use App\Policies\AssignmentPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Регистрируем только нестандартные политики
     */
    protected $policies = [
        \Spatie\Permission\Models\Role::class => \App\Policies\RolePolicy::class,
        \Spatie\Permission\Models\Permission::class => \App\Policies\PermissionPolicy::class,
        \Illuminate\Notifications\DatabaseNotification::class => \App\Policies\DatabaseNotificationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // 🔥 Администратор может всё
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // Gates для Assignment (если нужны для API или других мест)
        Gate::define('confirm_assignment', [\App\Policies\AssignmentPolicy::class, 'confirm']);
        Gate::define('reject_assignment', [\App\Policies\AssignmentPolicy::class, 'reject']);
        
        // Gates для Shield - используем политики
        // Gate::define('view_shield', function ($user) {
        //     return $user->hasRole('admin');
        // });
        
        // Gate::define('manage_roles', function ($user) {
        //     return $user->hasRole('admin');
        // });
        
        // Gate::define('manage_permissions', function ($user) {
        //     return $user->hasRole('admin');
        // });
    }
}
