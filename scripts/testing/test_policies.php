<?php

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Тестирование политик\n\n";

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
$assignment = Assignment::where('user_id', $executor->id)
    ->where('status', 'pending')
    ->first();

if (!$assignment) {
    echo "❌ Нет назначений в статусе pending\n";
    exit;
}

echo "📋 Назначение #{$assignment->id}:\n";
echo "  • Статус: {$assignment->status}\n";
echo "  • Тип: {$assignment->assignment_type}\n";
echo "  • Пользователь ID: {$assignment->user_id}\n\n";

// 3. Проверка политик
echo "🎯 Проверка AssignmentPolicy:\n";

// Проверим, какая политика используется
$policy = Gate::getPolicyFor($assignment);
echo "  • Используемая политика: " . ($policy ? get_class($policy) : 'НЕТ!') . "\n";

// Проверим методы политики
echo "  • Метод confirm существует: " . (method_exists($policy, 'confirm') ? '✅' : '❌') . "\n";
echo "  • Метод reject существует: " . (method_exists($policy, 'reject') ? '✅' : '❌') . "\n\n";

// Проверим через Gate
echo "🔐 Проверка через Gate::allows():\n";
echo "  • confirm: " . (Gate::allows('confirm', $assignment) ? '✅' : '❌') . "\n";
echo "  • reject: " . (Gate::allows('reject', $assignment) ? '✅' : '❌') . "\n\n";

// Проверим через can() пользователя
echo "👤 Проверка через User::can():\n";
echo "  • confirm: " . ($executor->can('confirm', $assignment) ? '✅' : '❌') . "\n";
echo "  • reject: " . ($executor->can('reject', $assignment) ? '✅' : '❌') . "\n";

