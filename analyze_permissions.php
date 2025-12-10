<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "🔍 Анализ системы разрешений Spatie...\n\n";

// 1. Проверяем стандартные таблицы Spatie
$spatieTables = [
    'permissions' => 'Разрешения',
    'roles' => 'Роли', 
    'model_has_permissions' => 'Связь моделей с разрешениями',
    'model_has_roles' => 'Связь моделей с ролями',
    'role_has_permissions' => 'Связь ролей с разрешениями'
];

echo "📋 Стандартные таблицы Spatie Permission:\n";
foreach ($spatieTables as $table => $description) {
    $exists = DB::select("SHOW TABLES LIKE '$table'");
    $count = $exists ? DB::table($table)->count() : 0;
    echo "   " . ($exists ? '✅' : '❌') . " $table: $description ($count записей)\n";
}

// 2. Проверяем, не дублируются ли разрешения
echo "\n📊 Анализ разрешений:\n";
$permissions = DB::table('permissions')->get();
$permissionNames = [];
$duplicates = [];

foreach ($permissions as $permission) {
    $name = $permission->name;
    if (in_array($name, $permissionNames)) {
        $duplicates[] = $name;
    }
    $permissionNames[] = $name;
}

if (count($duplicates) > 0) {
    echo "   ⚠️  Найдены дубликаты разрешений:\n";
    foreach ($duplicates as $dup) {
        echo "      - $dup\n";
    }
} else {
    echo "   ✅ Дубликатов нет\n";
}

// 3. Проверяем guard
echo "\n🛡️ Guard name для разрешений:\n";
$guards = DB::table('permissions')->distinct()->pluck('guard_name');
foreach ($guards as $guard) {
    $count = DB::table('permissions')->where('guard_name', $guard)->count();
    echo "   - $guard: $count разрешений\n";
}

// 4. Проверяем использование Filament
echo "\n🎯 Интеграция с Filament:\n";

// Ищем Filament политики
$filamentPermissions = DB::table('permissions')
    ->where('name', 'like', '%_any_%')
    ->orWhere('name', 'like', 'access_filament')
    ->get();

if ($filamentPermissions->count() > 0) {
    echo "   ✅ Найдены разрешения Filament\n";
    echo "   Примеры:\n";
    foreach ($filamentPermissions->take(5) as $perm) {
        echo "      - {$perm->name} (guard: {$perm->guard_name})\n";
    }
} else {
    echo "   ℹ️  Не найдено разрешений Filament\n";
}

// 5. Рекомендации
echo "\n💡 Рекомендации:\n";
echo "   1. 5 таблиц - норма для Spatie Laravel Permission\n";
echo "   2. 88 разрешений могут быть сгенерированы Filament автоматически\n";
echo "   3. Проверить конфигурацию в config/permission.php\n";
echo "   4. Убедиться, что guard_name везде 'web' (если не используется API)\n";
