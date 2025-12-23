<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ТЕСТИРОВАНИЕ WORKFLOW НАЗНАЧЕНИЙ ===\n\n";

// 1. Создаем назначение как инициатор
echo "1. Создание назначения инициатором:\n";
$initiator = App\Models\User::where('email', 'initiator1@example.com')->first();
auth()->login($initiator);

$assignment = App\Models\Assignment::create([
    'assignment_type' => 'brigadier_schedule',
    'user_id' => 7, // executor1
    'role_in_shift' => 'brigadier',
    'source' => 'initiator',
    'planned_date' => now()->addDays(1)->format('Y-m-d'),
    'planned_start_time' => '09:00',
    'planned_duration_hours' => 8,
    'status' => 'pending',
    'assignment_comment' => 'Тестовое назначение',
]);

echo "   ✅ Назначение создано ID: {$assignment->id}\n";
echo "   created_by: {$assignment->created_by} (должно быть {$initiator->id})\n\n";

// 2. Проверяем, что исполнитель видит назначение
echo "2. Проверка прав исполнителя:\n";
$executor = App\Models\User::find(7);
auth()->login($executor);

echo "   Может видеть назначение: " . (auth()->user()->can('view', $assignment) ? '✅' : '❌') . "\n";
echo "   Может подтвердить: " . (auth()->user()->can('confirm', $assignment) ? '✅' : '❌') . "\n";
echo "   Может отклонить: " . (auth()->user()->can('reject', $assignment) ? '✅' : '❌') . "\n\n";

// 3. Подтверждаем как исполнитель
echo "3. Подтверждение назначения исполнителем:\n";
if (auth()->user()->can('confirm', $assignment)) {
    $assignment->confirm();
    echo "   ✅ Назначение подтверждено\n";
    echo "   Статус: {$assignment->status}\n";

    // Проверяем создание смены
    if ($assignment->shift_id) {
        $shift = App\Models\Shift::find($assignment->shift_id);
        echo "   Смена создана: #{$shift->id} ({$shift->status})\n";
    } else {
        echo "   ⚠️ Смена не создана автоматически\n";
    }
} else {
    echo "   ❌ Исполнитель не может подтвердить\n";
}

echo "\n=== РЕЗУЛЬТАТЫ ===\n";
echo "- Политики работают: " . (auth()->user()->can('view', $assignment) ? '✅' : '❌') . "\n";
echo "- created_by заполнен: " . ($assignment->created_by ? '✅' : '❌') . "\n";
echo "- Автоматическая смена: " . ($assignment->shift_id ? '✅' : '❌') . "\n";
