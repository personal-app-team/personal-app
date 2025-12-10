<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class FindProblematicMigrations extends Command
{
    protected $signature = 'app:find-problematic-migrations';
    protected $description = 'Находит миграции, ссылающиеся на несуществующие таблицы';

    public function handle()
    {
        $this->info('🔍 Поиск проблемных миграций...');

        // Получаем список существующих таблиц
        $tables = DB::select('SHOW TABLES');
        $existingTables = array_map(function($row) {
            return current((array)$row);
        }, $tables);

        $this->line("📊 Всего таблиц в БД: " . count($existingTables));
        
        $migrationFiles = File::files(database_path('migrations'));
        $problematicMigrations = [];

        foreach ($migrationFiles as $file) {
            $filename = $file->getFilename();
            $content = File::get($file->getPathname());
            
            // Ищем упоминания таблиц в миграции
            preg_match_all('/(?:CREATE|ALTER|DROP|TRUNCATE|RENAME)\s+TABLE\s+(?:IF\s+(?:NOT\s+)?EXISTS\s+)?`?(\w+)`?/i', $content, $matches);
            
            $tablesInMigration = array_unique(array_filter($matches[1]));
            $nonExistentTables = [];
            
            foreach ($tablesInMigration as $table) {
                // Исключаем системные таблицы и migrations
                if ($table === 'migrations' || 
                    str_starts_with($table, '#') || 
                    empty($table)) {
                    continue;
                }
                
                if (!in_array($table, $existingTables)) {
                    $nonExistentTables[] = $table;
                }
            }
            
            if (!empty($nonExistentTables)) {
                $problematicMigrations[$filename] = $nonExistentTables;
            }
        }

        if (count($problematicMigrations) > 0) {
            $this->error('❌ Найдены миграции, ссылающиеся на несуществующие таблицы:');
            
            foreach ($problematicMigrations as $migration => $tables) {
                $this->line("  📄 {$migration}");
                $this->line("     Ссылается на: " . implode(', ', $tables));
            }
            
            // Сохраняем список в файл для использования в миграции очистки
            $this->saveProblematicList($problematicMigrations);
            
            return 1;
        } else {
            $this->info('✅ Все миграции ссылаются только на существующие таблицы');
            return 0;
        }
    }
    
    private function saveProblematicList($problematicMigrations)
    {
        $list = [];
        foreach ($problematicMigrations as $migration => $tables) {
            $migrationName = str_replace('.php', '', $migration);
            $list[] = "'{$migrationName}'";
        }
        
        $content = "<?php\n\n// Автоматически сгенерированный список проблемных миграций\n" .
                   "// Дата создания: " . date('Y-m-d H:i:s') . "\n" .
                   "return [\n    " . implode(",\n    ", $list) . "\n];\n";
        
        File::put(storage_path('logs/problematic_migrations.php'), $content);
        $this->line("\n📁 Список сохранен: " . storage_path('logs/problematic_migrations.php'));
    }
}
