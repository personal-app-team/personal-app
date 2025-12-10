<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AnalyzeLogging extends Command
{
    protected $signature = 'logging:analyze';
    protected $description = 'Анализ покрытия логирования ActivityLog в моделях';

    public function handle()
    {
        $this->info('🔍 АНАЛИЗ СИСТЕМЫ ЛОГИРОВАНИЯ ACTIVITYLOG');
        $this->line('==========================================');
        
        $modelsPath = app_path('Models');
        $files = glob($modelsPath . '/*.php');
        
        $modelsWithLogging = [];
        $modelsWithoutLogging = [];
        $total = 0;
        
        foreach ($files as $file) {
            $modelName = basename($file, '.php');
            $content = file_get_contents($file);
            
            // ИСПРАВЛЕНИЕ: Ищем использование трейта LogsActivity (полный или короткий путь)
            $hasLogsActivity = Str::contains($content, 'LogsActivity') && 
                              (Str::contains($content, 'use LogsActivity') || 
                               Str::contains($content, 'use Spatie\\Activitylog\\Traits\\LogsActivity'));
            
            // Ищем метод getActivitylogOptions
            $hasLogOptions = Str::contains($content, 'getActivitylogOptions');
            
            $total++;
            
            if ($hasLogsActivity && $hasLogOptions) {
                $modelsWithLogging[] = $modelName;
            } else {
                $modelsWithoutLogging[] = $modelName;
            }
        }
        
        $this->newLine();
        $this->info('📊 СТАТИСТИКА ЛОГИРОВАНИЯ:');
        $this->line("Всего моделей: {$total}");
        $this->line("Моделей с логированием: " . count($modelsWithLogging) . 
                   " (" . round(count($modelsWithLogging) * 100 / $total) . "%)");
        $this->line("Моделей без логирования: " . count($modelsWithoutLogging) . 
                   " (" . round(count($modelsWithoutLogging) * 100 / $total) . "%)");
        
        $this->newLine();
        $this->info('✅ МОДЕЛИ С ЛОГИРОВАНИЕМ (' . count($modelsWithLogging) . '):');
        foreach ($modelsWithLogging as $model) {
            $this->line("  • {$model}");
        }
        
        $this->newLine();
        $this->info('❌ МОДЕЛИ БЕЗ ЛОГИРОВАНИЯ (' . count($modelsWithoutLogging) . '):');
        foreach ($modelsWithoutLogging as $model) {
            $this->line("  • {$model}");
        }
        
        // Проверяем конкретные модели, которые должны иметь логирование
        $this->newLine();
        $this->info('🔍 ПРОВЕРКА КОНКРЕТНЫХ МОДЕЛЕЙ:');
        
        $checkModels = [
            'ContractorWorker' => 'Должен иметь логирование (проверяем файл)',
            'MassPersonnelReport' => 'Должен иметь логирование (проверяем файл)',
            'Vacancy' => 'Должен иметь логирование (добавлен сегодня)',
            'VacancyCondition' => 'Должен иметь логирование (добавлен сегодня)',
            'VacancyRequirement' => 'Должен иметь логирование (добавлен сегодня)',
            'VacancyTask' => 'Должен иметь логирование (добавлен сегодня)',
        ];
        
        foreach ($checkModels as $model => $description) {
            if (in_array($model, $modelsWithLogging)) {
                $this->line("  ✅ {$model}: {$description} - ОК");
            } else {
                // Проверим содержание файла
                $filePath = app_path("Models/{$model}.php");
                if (File::exists($filePath)) {
                    $content = File::get($filePath);
                    $hasLogsActivity = Str::contains($content, 'LogsActivity');
                    $hasLogOptions = Str::contains($content, 'getActivitylogOptions');
                    
                    $this->line("  ❌ {$model}: {$description}");
                    $this->line("     LogsActivity: " . ($hasLogsActivity ? '✅' : '❌'));
                    $this->line("     getActivitylogOptions: " . ($hasLogOptions ? '✅' : '❌'));
                    
                    if ($hasLogsActivity && !$hasLogOptions) {
                        $this->line("     ⚠️  Есть трейт, но нет метода getActivitylogOptions()!");
                    }
                } else {
                    $this->line("  ❓ {$model}: Файл не найден!");
                }
            }
        }
        
        return Command::SUCCESS;
    }
}
