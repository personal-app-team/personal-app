<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class CheckBrokenRelations extends Command
{
    protected $signature = 'app:check-broken-relations';
    protected $description = 'Проверяет Filament Resources на устаревшие отношения';

    public function handle()
    {
        $this->info('🔍 Проверка ресурсов на устаревшие отношения...');
        
        $resourcesPath = app_path('Filament/Resources');
        $resourceFiles = File::files($resourcesPath);
        
        $brokenRelations = [];
        $totalChecked = 0;
        
        foreach ($resourceFiles as $file) {
            if ($file->getExtension() === 'php' && str_contains($file->getFilename(), 'Resource.php')) {
                $totalChecked++;
                $content = File::get($file->getPathname());
                
                // Ищем счетчики отношений
                if (preg_match_all("/counts\s*\(\s*['\"]([^'\"]+)['\"]\s*\)/", $content, $matches)) {
                    foreach ($matches[1] as $relation) {
                        $resourceName = str_replace('.php', '', $file->getFilename());
                        
                        if (!isset($brokenRelations[$resourceName])) {
                            $brokenRelations[$resourceName] = [];
                        }
                        
                        $brokenRelations[$resourceName][] = $relation;
                    }
                }
            }
        }
        
        $this->line("✅ Проверено ресурсов: {$totalChecked}");
        
        if (count($brokenRelations) > 0) {
            $this->error('❌ Найдены ресурсы со счетчиками отношений:');
            
            foreach ($brokenRelations as $resource => $relations) {
                $uniqueRelations = array_unique($relations);
                $this->line("  📁 {$resource}: " . implode(', ', $uniqueRelations));
            }
            
            $this->line("\n⚠️  ВНИМАНИЕ: Проверьте эти отношения:");
            $this->line("   - 'users' - поле contract_type_id удалено из таблицы users");
            $this->line("   - 'users' - поле tax_status_id удалено из таблицы users");
            $this->line("   - Поля перенесены в employment_history");
            
            return 1;
        } else {
            $this->info('✅ Проблемных счетчиков отношений не найдено');
            return 0;
        }
    }
}
