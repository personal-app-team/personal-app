<?php
// app/Console/Commands/AnalyzeLoggingDetailed.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AnalyzeLoggingDetailed extends Command
{
    protected $signature = 'logging:analyze:detailed';
    protected $description = 'Детальный анализ логирования с проверкой кода';

    public function handle()
    {
        $this->info('🔍 ДЕТАЛЬНЫЙ АНАЛИЗ ЛОГИРОВАНИЯ ACTIVITYLOG');
        $this->line('============================================');
        
        $models = [
            // Финансовые операции (ВЫСОКИЙ приоритет)
            'Contractor' => ['финансы', 'высокий'],
            'ContractorWorker' => ['финансы', 'высокий'],
            'MassPersonnelReport' => ['финансы', 'высокий'],
            'Expense' => ['финансы', 'высокий'],
            'Compensation' => ['финансы', 'высокий'],
            'ContractorRate' => ['финансы', 'высокий'],
            
            // Рекрутинг (ВЫСОКИЙ приоритет)
            'RecruitmentRequest' => ['рекрутинг', 'высокий'],
            'Candidate' => ['рекрутинг', 'высокий'],
            'CandidateDecision' => ['рекрутинг', 'высокий'],
            'CandidateStatusHistory' => ['рекрутинг', 'высокий'],
            'Interview' => ['рекрутинг', 'высокий'],
            'HiringDecision' => ['рекрутинг', 'высокий'],
            'PositionChangeRequest' => ['рекрутинг', 'высокий'],
            'TraineeRequest' => ['рекрутинг', 'высокий'],
            'Vacancy' => ['рекрутинг', 'высокий'],
            'VacancyCondition' => ['рекрутинг', 'высокий'],
            'VacancyRequirement' => ['рекрутинг', 'высокий'],
            'VacancyTask' => ['рекрутинг', 'высокий'],
            
            // Управление персоналом (СРЕДНИЙ приоритет)
            'User' => ['персонал', 'средний'],
            'EmploymentHistory' => ['персонал', 'средний'],
            'Department' => ['персонал', 'средний'],
            'Assignment' => ['персонал', 'средний'],
            
            // Workflow и операции (ВЫСОКИЙ приоритет)
            'WorkRequest' => ['workflow', 'высокий'],
            'WorkRequestStatus' => ['workflow', 'высокий'],
            'Shift' => ['workflow', 'высокий'],
            
            // Проекты и геолокации (СРЕДНИЙ приоритет)
            'Project' => ['проекты', 'средний'],
            'Address' => ['проекты', 'средний'],
            'VisitedLocation' => ['проекты', 'средний'],
            'Photo' => ['проекты', 'средний'],
            
            // Справочники (НИЗКИЙ приоритет)
            'Category' => ['справочники', 'низкий'],
            'Specialty' => ['справочники', 'низкий'],
            'WorkType' => ['справочники', 'низкий'],
            'ContractType' => ['справочники', 'низкий'],
            'TaxStatus' => ['справочники', 'низкий'],
            'AddressTemplate' => ['справочники', 'низкий'],
            'PurposeTemplate' => ['справочники', 'низкий'],
            'InitiatorGrant' => ['справочники', 'низкий'],
        ];
        
        $results = [];
        
        foreach ($models as $model => $info) {
            $filePath = app_path("Models/{$model}.php");
            
            if (!File::exists($filePath)) {
                $results[$model] = [
                    'status' => '❌', 
                    'category' => $info[0], 
                    'priority' => $info[1],
                    'log' => '—', 
                    'options' => '—',
                    'use_type' => 'файл не найден'
                ];
                continue;
            }
            
            $content = File::get($filePath);
            
            // Проверяем использование трейта LogsActivity
            $hasLogsActivity = Str::contains($content, 'LogsActivity');
            
            // Проверяем наличие метода getActivitylogOptions
            $hasLogOptions = Str::contains($content, 'getActivitylogOptions');
            
            // Проверяем наличие use Spatie\Activitylog\Traits\LogsActivity
            $hasFullUse = Str::contains($content, 'use Spatie\\Activitylog\\Traits\\LogsActivity');
            
            // Проверяем наличие use LogsActivity (короткий вариант)
            $hasShortUse = Str::contains($content, 'use LogsActivity') && 
                          !Str::contains($content, 'use Spatie\\Activitylog\\Traits\\LogsActivity');
            
            $logStatus = $hasLogsActivity && $hasLogOptions ? '✅' : '❌';
            
            $useType = $hasFullUse ? 'полный' : ($hasShortUse ? 'короткий' : 'нет');
            
            $results[$model] = [
                'status' => $logStatus,
                'category' => $info[0],
                'priority' => $info[1],
                'log' => $hasLogsActivity ? '✅' : '❌',
                'options' => $hasLogOptions ? '✅' : '❌',
                'use_type' => $useType,
            ];
        }
        
        // Группируем по категориям
        $groupedResults = [];
        foreach ($results as $model => $data) {
            $category = $data['category'] ?? 'неизвестно';
            if (!isset($groupedResults[$category])) {
                $groupedResults[$category] = [];
            }
            
            $groupedResults[$category][] = [
                'Модель' => $model,
                'Статус' => $data['status'],
                'Приоритет' => $data['priority'],
                'LogsActivity' => $data['log'],
                'getActivitylogOptions' => $data['options'],
                'Тип use' => $data['use_type'],
            ];
        }
        
        foreach ($groupedResults as $category => $models) {
            $this->newLine();
            $this->info("📁 КАТЕГОРИЯ: " . strtoupper($category));
            $this->table(
                ['Модель', 'Статус', 'Приоритет', 'LogsActivity', 'getActivitylogOptions', 'Тип use'],
                $models
            );
        }
        
        // Статистика
        $total = count($results);
        $withLogging = count(array_filter($results, fn($r) => $r['status'] === '✅'));
        $withoutLogging = $total - $withLogging;
        
        $this->newLine();
        $this->info('📈 СТАТИСТИКА:');
        $this->line("Всего проверено моделей: {$total}");
        $this->line("С логированием: {$withLogging} (" . round($withLogging * 100 / $total) . "%)");
        $this->line("Без логирования: {$withoutLogging} (" . round($withoutLogging * 100 / $total) . "%)");
        
        // Рекомендации
        $this->newLine();
        $this->info('🎯 РЕКОМЕНДАЦИИ ПО ПРИОРИТЕТАМ:');
        
        $priorities = ['высокий', 'средний', 'низкий'];
        
        foreach ($priorities as $priority) {
            $modelsWithout = array_filter($results, fn($r) => 
                $r['priority'] === $priority && $r['status'] === '❌'
            );
            
            if (count($modelsWithout) > 0) {
                $this->line("\n{$priority}:");
                foreach ($modelsWithout as $model => $data) {
                    $this->line("  • {$model} ({$data['category']})");
                }
            }
        }
        
        return Command::SUCCESS;
    }
}
