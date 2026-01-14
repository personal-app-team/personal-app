<?php

return [
    'shield_resource' => [
        'should_register_navigation' => true,
        'slug' => 'shield/roles',
        'navigation_sort' => -1,
        'navigation_badge' => true,
        'navigation_group' => '👑 Права доступа',
        'is_globally_searchable' => false,
        'show_model_path' => true,
    ],
    'auth_provider_model' => [
        'fqcn' => 'App\\Models\\User',
    ],
    'super_admin' => [
        'enabled' => true,
        'name' => 'admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],
    'panel_user' => [
        'enabled' => false,
    ],
    'permission_prefixes' => [
        'resource' => [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ],
        'page' => 'page',
        'widget' => 'widget',
    ],
    'entities' => [
        'pages' => true,
        'widgets' => true,
        'resources' => true,
        'custom_permissions' => true,
    ],
    'generator' => [
        'option' => 'policies_and_permissions',
        'policy_directory' => base_path('app/Policies'),
        'policy_namespace' => 'App\\Policies',
        'except' => [
            // Исключаем ActivityLogResource из генерации разрешений
            \App\Filament\Resources\ActivityLogResource::class,
        ],
    ],
    'exclude' => [
        'enabled' => true,
        'pages' => [
            'Dashboard',
        ],
        'widgets' => [],
        'resources' => [
            // Shield ресурсы показываем только админам
            \BezhanSalleh\FilamentShield\Resources\RoleResource::class => [
                'should_show_navigation' => fn() => auth()->user()?->hasRole('admin') ?? false,
            ],
            \BezhanSalleh\FilamentShield\Resources\PermissionResource::class => [
                'should_show_navigation' => fn() => auth()->user()?->hasRole('admin') ?? false,
            ],
        ],
    ],
    'register_role_policy' => [
        'enabled' => false,
    ],
    'teams' => [
        'enabled' => false,
    ],
];
