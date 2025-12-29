<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "🔍 Проверка прав доступа инициатора:\n\n";

// 1. Получаем роль инициатора
$role = Role::where('name', 'initiator')->first();

if (!$role) {
    echo "❌ Роль 'initiator' не найдена\n";
    exit(1);
}

// 2. Проверяем разрешения для Assignment
echo "Права для Assignment:\n";
$assignmentPermissions = [
    'view_any_assignment',
    'view_assignment', 
    'create_assignment',
    'update_assignment',
    'delete_assignment',
];

foreach ($assignmentPermissions as $perm) {
    $permission = Permission::where('name', $perm)->first();
    $hasPerm = $role->hasPermissionTo($perm);
    echo $hasPerm ? "✅ " : "❌ ";
    echo "{$perm}";
    if (!$permission) echo " (разрешение не существует в системе)";
    echo "\n";
}

// 3. Проверяем разрешения для WorkRequest (чтобы понять паттерн)
echo "\nПрава для WorkRequest (для сравнения):\n";
$workRequestPermissions = Permission::where('name', 'like', '%workrequest%')->get();
foreach ($workRequestPermissions as $perm) {
    $hasPerm = $role->hasPermissionTo($perm);
    echo $hasPerm ? "✅ " : "❌ ";
    echo "{$perm->name}\n";
}

// 4. Проверяем Gates
echo "\nПроверка кастомных Gates:\n";
$user = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'initiator');
})->first();

if ($user) {
    echo "Тестовый пользователь инициатор: {$user->email}\n";
    
    // Проверяем Gate create_brigadier_schedule
    $canCreateSchedule = \Illuminate\Support\Facades\Gate::forUser($user)->allows('create_brigadier_schedule');
    echo "Gate 'create_brigadier_schedule': " . ($canCreateSchedule ? "✅ Разрешено" : "❌ Запрещено") . "\n";
    
    // Проверяем, есть ли разрешение create_assignment
    $hasCreateAssignment = $user->can('create_assignment');
    echo "Разрешение 'create_assignment': " . ($hasCreateAssignment ? "✅ Есть" : "❌ Нет") . "\n";
} else {
    echo "❌ Не найден пользователь с ролью инициатор\n";
}

echo "\n🎯 Вывод: Для создания назначения инициатору нужно:\n";
echo "1. Разрешение 'create_assignment' ✅\n";
echo "2. Разрешение 'view_any_assignment' (чтобы видеть список) ❌\n";
echo "3. Gate 'create_brigadier_schedule' (для создания расписания) ✅\n";
echo "\n💡 Возможно, проблема в отсутствии 'view_any_assignment' - без него ресурс не отображается в навигации.\n";
