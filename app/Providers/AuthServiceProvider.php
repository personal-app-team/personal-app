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
        // DatabaseNotification нужно регистрировать вручную, 
        // потому что это встроенная модель Laravel
        \Illuminate\Notifications\DatabaseNotification::class => \App\Policies\DatabaseNotificationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // 🔥 ВАЖНО: Администратор может всё
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        // 🔥 ТОЛЬКО нестандартные методы, которые не находит Laravel автоматически
        Gate::define('confirm_assignment', [AssignmentPolicy::class, 'confirm']);
        Gate::define('reject_assignment', [AssignmentPolicy::class, 'reject']);
        
        // ❌ УДАЛИТЬ все остальные Gates! Они не нужны!
        // Gate::define('access_admin_panel', ...) - НЕ НУЖЕН!
        // Filament использует свои проверки для доступа к панели
    }
}
