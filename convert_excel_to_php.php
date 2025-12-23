<?php
// convert_excel_to_php.php - запусти: php convert_excel_to_php.php

$csvFile = 'permissions.csv';

if (!file_exists($csvFile)) {
    echo "❌ Файл {$csvFile} не найден.\n";
    echo "Сохрани permissions.xlsx как CSV (UTF-8, запятая)\n";
    exit(1);
}

$permissions = [];

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    // Пропускаем заголовок (первую строку)
    fgetcsv($handle, 1000, ",");
    
    $lineNumber = 1;
    
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $lineNumber++;
        
        if (count($data) < 3) {
            echo "⚠️  Строка {$lineNumber}: мало полей\n";
            continue;
        }
        
        $name = trim($data[0]);
        $group = trim($data[1]);
        $description = trim($data[2]);
        
        if (empty($name)) {
            echo "⚠️  Строка {$lineNumber}: пустое имя\n";
            continue;
        }
        
        $permissions[$name] = [
            'group' => $group,
            'description' => $description
        ];
    }
    fclose($handle);
}

echo "✅ Обработано: " . count($permissions) . " разрешений\n";

// Генерация массива для PermissionSeeder
$phpCode = "    /**\n     * Все разрешения из Excel файла\n     */\n    private array \$excelPermissions = [\n\n";

foreach ($permissions as $name => $data) {
    $phpCode .= "        '" . addslashes($name) . "' => ['group' => '" . addslashes($data['group']) . "', 'description' => '" . addslashes($data['description']) . "'],\n";
}

$phpCode .= "    ];\n";

file_put_contents('generated_permissions.php', $phpCode);
echo "✅ Массив сохранен в generated_permissions.php\n";

// Статистика
$groups = [];
foreach ($permissions as $data) {
    $group = $data['group'];
    $groups[$group] = ($groups[$group] ?? 0) + 1;
}

echo "\n📊 Статистика по группам:\n";
arsort($groups);
foreach ($groups as $group => $count) {
    echo "  - {$group}: {$count}\n";
}
