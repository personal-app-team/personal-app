<?php

use App\Models\User;
use App\Models\Assignment;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Тестирование разрешений назначений\n\n";

// 1. Исполнитель
$executor = User::where('email', 'executor1@example.com')->first();
Auth::login($executor);

echo "👤 Исполнитель: {$executor->email}\n";

// 2. Назначение исполнителя
$assignment = Assignment::where('user_id', $executor->id)
    ->where('status', 'pending')
    ->first();

echo "📋 Назначение #{$assignment->id}:\n";
echo "  • Исполнитель: {$assignment->user_id}\n";
echo "  • Статус: {$assignment->status}\n\n";

// 3. Проверка через Gate
echo "🔐 Проверка Gates:\n";
echo "  • confirm_assignment: " . ($executor->can('confirm_assignment', $assignment) ? '✅' : '❌') . "\n";
echo "  • reject_assignment: " . ($executor->can('reject_assignment', $assignment) ? '✅' : '❌') . "\n\n";

// 4. Проверка через Policy
echo "🎯 Проверка Policies:\n";
echo "  • confirm: " . ($executor->can('confirm', $assignment) ? '✅' : '❌') . "\n";
echo "  • reject: " . ($executor->can('reject', $assignment) ? '✅' : '❌') . "\n\n";

// 5. Проверка getEloquentQuery
echo "📊 Проверка getEloquentQuery:\n";
$query = \App\Filament\Resources\AssignmentResource::getEloquentQuery();
echo "  • Записей видно: " . $query->count() . "\n";
echo "  • Наша запись в списке: " . ($query->where('id', $assignment->id)->exists() ? '✅' : '❌') . "\n";
