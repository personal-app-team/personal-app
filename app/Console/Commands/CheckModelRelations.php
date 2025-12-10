<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class CheckModelRelations extends Command
{
    protected $signature = 'app:check-model-relations';
    protected $description = 'Проверяет модели на устаревшие отношения';

    public function handle()
    {
        $this->info('🔍 Проверка моделей на устаревшие отношения...');
        
        $modelsPath = app_path('Models');
        $modelFiles = File::files($modelsPath);
        
        $potentialProblems = [];
        $totalChecked = 0;
        
        foreach ($modelFiles as $file) {
            if ($file->getExtension() === 'php') {
                $totalChecked++;
                $modelName = 'App\\Models\\' . $file->getFilenameWithoutExtension();
                
                try {
                    if (class_exists($modelName)) {
                        $content = File::get($file->getPathname());
                        
                        // Ищем отношения hasMany или belongsTo к User с contract_type_id или tax_status_id
                        if (str_contains($content, 'hasMany(User::class)') || 
                            str_contains($content, 'belongsTo(User::class)')) {
                            
                            // Проверяем, не относится ли это к устаревшим полям
                            if (str_contains($content, 'contract_type_id') || 
                                str_contains($content, 'tax_status_id')) {
                                
                                $potentialProblems[] = [
                                    'model' => $file->getFilenameWithoutExtension(),
                                    'reason' => 'Отношение к User с устаревшими полями contract_type_id или tax_status_id'
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->warn("⚠️ Не удалось проверить {$file->getFilename()}: " . $e->getMessage());
                }
            }
        }
        
        $this->line("✅ Проверено моделей: {$totalChecked}");
        
        if (count($potentialProblems) > 0) {
            $this->error('❌ Найдены потенциальные проблемы в моделях:');
            
            foreach ($potentialProblems as $problem) {
                $this->line("  📁 {$problem['model']}: {$problem['reason']}");
            }
            
            $this->line("\n🔧 Модели, которые нужно проверить:");
            $this->line("   - TaxStatus (уже исправляем)");
            $this->line("   - Contractor (может иметь contract_type_id/tax_status_id)");
            $this->line("   - Shift (проверить tax_status_id)");
            
            return 1;
        } else {
            $this->info('✅ Проблемных моделей не найдено');
            return 0;
        }
    }
}
