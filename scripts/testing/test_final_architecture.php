<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Тестирование финальной архитектуры разрешений\n\n";

use App\Models\User;
use App\Models\Assignment;
use Illuminate\Support\Facades\Auth;

// 1. Исполнитель
$executor = User::where('email', 'executor1@example.com')->first();
Auth::login($executor);

echo "👤 Пользователь: {$executor->email}\n";
echo "📊 Роли: " . implode(', ', $executor->roles->pluck('name')->toArray()) . "\n\n";

// 2. Назначение
$assignment = Assignment::find(10);
echo "📋 Назначение #{$assignment->id}:\n";
echo "  • Статус: {$assignment->status}\n\n";

// 3. Проверка Gate confirm_assignment (должно быть true)
echo "🔐 Проверка Gates (зарегистрированы в AuthServiceProvider):\n";
echo "  • confirm_assignment: " . ($executor->can('confirm_assignment', $assignment) ? '✅' : '❌') . "\n";
echo "  • reject_assignment: " . ($executor->can('reject_assignment', $assignment) ? '✅' : '❌') . "\n\n";

// 4. Проверка разрешений Shield (должны быть только назначенные)
echo "🛡️ Проверка разрешений Filament Shield:\n";
$permissions = $executor->getAllPermissions()->pluck('name')->sort();
echo "  • Всего разрешений: " . $permissions->count() . "\n";
echo "  • Примеры разрешений:\n";
foreach ($permissions->take(10) as $permission) {
    echo "    - {$permission}\n";
}
if ($permissions->count() > 10) {
    echo "    ... и еще " . ($permissions->count() - 10) . "\n";
}
echo "\n";

// 5. Проверка доступа к ресурсам
echo "🚫 Проверка доступа к НЕразрешенным ресурсам (должны быть false):\n";
$unauthorizedResources = ['expense', 'visited_location', 'photo', 'activity_log'];
foreach ($unauthorizedResources as $resource) {
    $result = $executor->can("view_any_{$resource}");
    echo "  • view_any_{$resource}: " . ($result ? '❌ НЕОЖИДАННО true!' : '✅ false') . "\n";
    
    // Если true - ищем причину
    if ($result) {
        $resourceClass = "App\\Filament\\Resources\\" . ucfirst($resource) . "Resource";
        if (class_exists($resourceClass)) {
            $reflection = new ReflectionClass($resourceClass);
            echo "    ⚠️  Проверьте методы canViewAny, canCreate и т.д. в {$resourceClass}\n";
        }
    }
}

// 6. Проверка AssignmentPolicy напрямую
echo "\n🎯 Проверка AssignmentPolicy напрямую:\n";
$policy = new \App\Policies\AssignmentPolicy();
echo "  • confirm(): " . ($policy->confirm($executor, $assignment) ? '✅ true' : '❌ false') . "\n";

