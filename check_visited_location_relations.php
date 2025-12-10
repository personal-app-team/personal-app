<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Проверка связей VisitedLocation...\n\n";

// Проверяем модель VisitedLocation
echo "1. Модель VisitedLocation:\n";
$visitedLocation = new App\Models\VisitedLocation;
echo "   Таблица: " . $visitedLocation->getTable() . "\n";
echo "   Полиморфное отношение: visitable (morphTo)\n\n";

// Проверяем модель Shift
echo "2. Модель Shift:\n";
if (method_exists('App\Models\Shift', 'visitedLocations')) {
    echo "   ✅ Имеет отношение visitedLocations()\n";
    
    // Проверяем тип отношения
    $shift = new App\Models\Shift;
    $reflection = new ReflectionMethod($shift, 'visitedLocations');
    $returnType = $reflection->getReturnType();
    echo "   Тип возврата: " . ($returnType ? $returnType->getName() : 'не указан') . "\n";
} else {
    echo "   ❌ Нет отношения visitedLocations()\n";
}

// Проверяем модель MassPersonnelReport
echo "\n3. Модель MassPersonnelReport:\n";
if (method_exists('App\Models\MassPersonnelReport', 'visitedLocations')) {
    echo "   ✅ Имеет отношение visitedLocations()\n";
    
    $report = new App\Models\MassPersonnelReport;
    $reflection = new ReflectionMethod($report, 'visitedLocations');
    $returnType = $reflection->getReturnType();
    echo "   Тип возврата: " . ($returnType ? $returnType->getName() : 'не указан') . "\n";
} else {
    echo "   ❌ Нет отношения visitedLocations()\n";
}

// Проверяем структуру таблицы visited_locations
echo "\n4. Структура таблицы visited_locations:\n";
use Illuminate\Support\Facades\DB;
try {
    $columns = DB::select("SHOW COLUMNS FROM visited_locations");
    $hasVisitable = false;
    foreach ($columns as $column) {
        if ($column->Field === 'visitable_type' || $column->Field === 'visitable_id') {
            $hasVisitable = true;
        }
        echo "   - " . $column->Field . " : " . $column->Type . "\n";
    }
    
    if ($hasVisitable) {
        echo "   ✅ Имеет поля для полиморфной связи\n";
    } else {
        echo "   ❌ Нет полей для полиморфной связи\n";
    }
} catch (Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}
