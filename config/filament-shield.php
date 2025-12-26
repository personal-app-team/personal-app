<?php

return [
    'shield_resource' => [
        'should_register_navigation' => true,
        'slug' => 'shield/roles',
        'navigation_sort' => -1,
        'navigation_badge' => true,
        'navigation_group' => 'Права доступа',
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
        'custom_permissions' => false,
    ],
    'generator' => [
        'option' => 'permissions', // ИЗМЕНЕНО: только разрешения, без политик
        'policy_directory' => 'Policies',
        'policy_namespace' => 'App\\Policies',
        'except' => [
            'AssignmentResource',
            'ShiftResource',
            'WorkRequestResource',
            'UserResource',
            'ExpenseResource',
            'CompensationResource',
            'TraineeRequestResource',
        ],
    ],
    'exclude' => [
        'enabled' => true,
        'pages' => [
            'Dashboard',
        ],
        'widgets' => [],
        'resources' => [],
    ],
    'register_role_policy' => [
        'enabled' => false, // ИЗМЕНЕНО: отключаем регистрацию политик ролей
    ],
    'teams' => [
        'enabled' => false,
    ],
];
