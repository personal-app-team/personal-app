<?php

use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Автозагрузчик уже будет при запуске через sail

echo "🔍 Проверка разрешений для сценария 01\n\n";

$rolesToCheck = ['initiator', 'executor', 'dispatcher'];
$criticalPermissions = [
    'create_assignment',
    'view_assignment',
    'view_any_assignment',
    'update_assignment',
    'create_work::request',
    'view_work::request',
    'view_any_work::request',
    'update_work::request',
    'view_user',
    'view_any_user',
];

foreach ($rolesToCheck as $roleName) {
    $role = Role::where('name', $roleName)->first();
    
    if (!$role) {
        echo "❌ Роль '{$roleName}' не найдена\n";
        continue;
    }
    
    echo "📋 Роль: {$roleName}\n";
    
    $rolePermissions = $role->permissions()->pluck('name')->toArray();
    
    foreach ($criticalPermissions as $perm) {
        $has = in_array($perm, $rolePermissions) ? '✅' : '❌';
        echo "  {$has} {$perm}\n";
    }
    
    // Дополнительная информация
    $totalPerms = count($rolePermissions);
    $relevantPerms = array_intersect($rolePermissions, $criticalPermissions);
    echo "  📊 Всего разрешений: {$totalPerms}, релевантных: " . count($relevantPerms) . "\n\n";
}

// Проверяем наличие всех разрешений в системе
echo "🎯 Проверка наличия всех необходимых разрешений в системе:\n";

$missingPermissions = [];
foreach ($criticalPermissions as $perm) {
    $exists = Permission::where('name', $perm)->exists();
    echo $exists ? "  ✅ {$perm}\n" : "  ❌ {$perm} (отсутствует)\n";
    if (!$exists) $missingPermissions[] = $perm;
}

if (!empty($missingPermissions)) {
    echo "\n⚠️  Отсутствующие разрешения:\n";
    foreach ($missingPermissions as $perm) {
        echo "  - {$perm}\n";
    }
} else {
    echo "\n✅ Все разрешения присутствуют в системе\n";
}

// Проверяем AssignmentPolicy
echo "\n🔐 Проверка AssignmentPolicy:\n";
$policyPath = 'app/Policies/AssignmentPolicy.php';
if (file_exists($policyPath)) {
    $content = file_get_contents($policyPath);
    $checks = [
        'initiator' => str_contains($content, 'hasRole(\'initiator\')'),
        'executor' => str_contains($content, 'hasRole(\'executor\')'),
        'dispatcher' => str_contains($content, 'hasRole(\'dispatcher\')'),
    ];
    
    foreach ($checks as $role => $has) {
        echo $has ? "  ✅ Упоминание роли '{$role}'\n" : "  ⚠️  Нет упоминания роли '{$role}'\n";
    }
} else {
    echo "  ❌ Файл AssignmentPolicy.php не найден\n";
}

// Проверяем WorkRequestPolicy
echo "\n🔐 Проверка WorkRequestPolicy:\n";
$policyPath = 'app/Policies/WorkRequestPolicy.php';
if (file_exists($policyPath)) {
    $content = file_get_contents($policyPath);
    $checks = [
        'initiator' => str_contains($content, 'hasRole(\'initiator\')'),
        'executor' => str_contains($content, 'hasRole(\'executor\')'),
        'dispatcher' => str_contains($content, 'hasRole(\'dispatcher\')'),
    ];
    
    foreach ($checks as $role => $has) {
        echo $has ? "  ✅ Упоминание роли '{$role}'\n" : "  ⚠️  Нет упоминания роли '{$role}'\n";
    }
} else {
    echo "  ❌ Файл WorkRequestPolicy.php не найден\n";
}

echo "\n🎉 Проверка завершена!\n";
