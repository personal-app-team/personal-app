<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'executor1@example.com')->first();
if ($user) {
    echo "=== Пользователь ===\n";
    echo "ID: " . $user->id . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Имя: " . ($user->full_name ?? $user->name) . "\n";
    
    echo "\n=== Роли ===\n";
    echo implode(', ', $user->getRoleNames()->toArray());
    
    echo "\n\n=== Разрешения ===\n";
    $permissions = $user->getAllPermissions()->pluck('name')->toArray();
    echo "Всего: " . count($permissions) . "\n";
    echo "Примеры: " . implode(', ', array_slice($permissions, 0, 5)) . "...\n";
    
    // Проверим конкретные разрешения
    echo "\n=== Проверка ключевых разрешений ===\n";
    $keyPermissions = ['view_any_assignment', 'view_any_shift', 'view_any_expense'];
    foreach ($keyPermissions as $perm) {
        echo $perm . ": " . ($user->can($perm) ? '✅' : '❌') . "\n";
    }
} else {
    echo "Пользователь не найден\n";
}
