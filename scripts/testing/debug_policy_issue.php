<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Детальная диагностика проблемы с политикой\n\n";

use App\Models\User;
use App\Models\Assignment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

// 1. Исполнитель
$executor = User::where('email', 'executor1@example.com')->first();
Auth::login($executor);

echo "👤 Пользователь: {$executor->email}\n";
echo "📊 Роли: " . implode(', ', $executor->roles->pluck('name')->toArray()) . "\n\n";

// 2. Назначение исполнителя
$assignment = Assignment::find(10);

echo "📋 Назначение #{$assignment->id}:\n";
echo "  • Статус: {$assignment->status}\n";
echo "  • Тип: {$assignment->assignment_type}\n";
echo "  • Пользователь ID: {$assignment->user_id}\n";
echo "  • Исполнитель ID: {$executor->id}\n\n";

// 3. Проверка условий политики вручную
echo "🎯 Проверка условий для confirm():\n";
echo "  • hasRole('executor'): " . ($executor->hasRole('executor') ? '✅' : '❌') . "\n";
echo "  • user_id совпадает: " . ($assignment->user_id === $executor->id ? '✅' : '❌') . "\n";
echo "  • status === 'pending': " . ($assignment->status === 'pending' ? '✅' : '❌') . "\n\n";

// 4. Проверка Gate::before
echo "🔐 Проверка Gate::before:\n";
echo "  • hasRole('admin'): " . ($executor->hasRole('admin') ? '✅' : '❌') . "\n";
echo "  • Gate::before вернет: " . ($executor->hasRole('admin') ? 'true' : 'null') . "\n\n";

// 5. Проверка разрешений Shield
echo "🛡️ Проверка разрешений Filament Shield:\n";
echo "  • can('update_assignment'): " . ($executor->can('update_assignment') ? '✅' : '❌') . "\n";
echo "  • can('confirm_assignment'): " . ($executor->can('confirm_assignment') ? '✅' : '❌') . "\n\n";

// 6. Прямой вызов политики
echo "🎯 Прямой вызов AssignmentPolicy::confirm():\n";
$policy = new \App\Policies\AssignmentPolicy();
$result = $policy->confirm($executor, $assignment);
echo "  • Результат: " . ($result ? '✅ true' : '❌ false') . "\n";

// 7. Проверка через Gate
echo "\n🔐 Проверка через Gate:\n";
echo "  • Gate::allows('confirm', \$assignment): " . (Gate::allows('confirm', $assignment) ? '✅' : '❌') . "\n";
echo "  • \$executor->can('confirm', \$assignment): " . ($executor->can('confirm', $assignment) ? '✅' : '❌') . "\n";

// 8. Проверка существования метода в политике
echo "\n📋 Проверка метода confirm в политике:\n";
if ($policy) {
    echo "  • Метод exists: " . (method_exists($policy, 'confirm') ? '✅' : '❌') . "\n";
    $reflection = new ReflectionMethod($policy, 'confirm');
    echo "  • Код метода:\n";
    $lines = file($reflection->getFileName());
    for ($i = $reflection->getStartLine(); $i < $reflection->getEndLine(); $i++) {
        echo "    " . $lines[$i-1];
    }
}

