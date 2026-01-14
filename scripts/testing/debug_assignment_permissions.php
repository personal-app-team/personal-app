<?php

use Illuminate\Support\Facades\Artisan;

echo "🐛 Отладка разрешений для назначений\n\n";

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Assignment;
use App\Policies\AssignmentPolicy;

// 1. Найдем исполнителя
$executor = User::where('email', 'executor1@example.com')->first();
if (!$executor) {
    echo "❌ Исполнитель не найден\n";
    exit;
}

echo "👤 Исполнитель: {$executor->email}\n";

// 2. Проверим разрешения
echo "🔑 Проверка разрешений:\n";
$permissions = ['confirm_assignment', 'reject_assignment', 'update_assignment'];
foreach ($permissions as $perm) {
    $has = $executor->can($perm) ? '✅' : '❌';
    echo "  {$has} {$perm}\n";
}

// 3. Найдем назначение для этого исполнителя
$assignment = Assignment::where('user_id', $executor->id)
    ->where('status', 'pending')
    ->first();

if (!$assignment) {
    echo "\n⚠️  Нет назначений со статусом pending для этого исполнителя\n";
    echo "   Создайте тестовое назначение через инициатора\n";
    exit;
}

echo "\n📋 Назначение #{$assignment->id}:\n";
echo "  • Исполнитель: {$assignment->user_id}\n";
echo "  • Статус: {$assignment->status}\n";
echo "  • Тип: {$assignment->assignment_type}\n";

// 4. Проверим политику
echo "\n🔐 Проверка политики:\n";
$policy = new AssignmentPolicy();

$canConfirm = $policy->confirm($executor, $assignment);
$canReject = $policy->reject($executor, $assignment);

echo "  • can confirm: " . ($canConfirm ? '✅' : '❌') . "\n";
echo "  • can reject: " . ($canReject ? '✅' : '❌') . "\n";

// 5. Проверим видимость через ресурс
echo "\n👁️  Проверка видимости в ресурсе:\n";
$isVisibleConfirm = $assignment->status === 'pending' && auth()->loginUsingId($executor->id) && $executor->can('confirm', $assignment);
$isVisibleReject = $assignment->status === 'pending' && $executor->can('reject', $assignment);

echo "  • Кнопка 'Подтвердить' видна: " . ($isVisibleConfirm ? '✅' : '❌') . "\n";
echo "  • Кнопка 'Отклонить' видна: " . ($isVisibleReject ? '✅' : '❌') . "\n";

echo "\n🎯 Диагностика завершена\n";
