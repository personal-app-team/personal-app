<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;

echo "📊 Проверка системы:\n\n";

// Проверяем namespace политик
$policyFiles = glob(__DIR__ . '/app/Policies/*.php');
$correctNamespace = 0;
$incorrectNamespace = 0;

foreach ($policyFiles as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'namespace App\Policies;') !== false) {
        $correctNamespace++;
    } elseif (strpos($content, 'namespace App\App\Policies;') !== false) {
        $incorrectNamespace++;
    }
}

echo "✅ Политики с правильным namespace: $correctNamespace\n";
echo "❌ Политики с неправильным namespace: $incorrectNamespace\n\n";

// Проверяем разрешения ролей
echo "📈 Разрешения у ролей:\n";
foreach (Role::withCount('permissions')->orderBy('name')->get() as $role) {
    echo "  - {$role->name}: {$role->permissions_count}\n";
}

echo "\n🎉 Проверка завершена!\n";
