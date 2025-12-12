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
        
        // Все 41 модели из системы с приоритетами
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
            'AddressProject' => ['проекты', 'средний'],
            'AddressTemplate' => ['проекты', 'средний'],
            'VisitedLocation' => ['проекты', 'средний'],
            'Photo' => ['проекты', 'средний'],
            'Purpose' => ['проекты', 'средний'],
            'PurposeTemplate' => ['проекты', 'средний'],
            'PurposeAddressRule' => ['проекты', 'средний'],
            'PurposePayerCompany' => ['проекты', 'средний'],
            
            // Справочники (НИЗКИЙ приоритет)
            'Category' => ['справочники', 'низкий'],
            'Specialty' => ['справочники', 'низкий'],
            'WorkType' => ['справочники', 'низкий'],
            'ContractType' => ['справочники', 'низкий'],
            'TaxStatus' => ['справочники', 'низкий'],
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
        
        // Детальная проверка для моделей без логирования
        $this->newLine();
        $this->info('🔧 ДЕТАЛЬНЫЙ АНАЛИЗ МОДЕЛЕЙ БЕЗ ЛОГИРОВАНИЯ:');
        
        foreach ($results as $model => $data) {
            if ($data['status'] === '❌') {
                $this->line("• {$model} ({$data['category']}, {$data['priority']} приоритет):");
                if ($data['log'] === '❌') {
                    $this->line("  - Не использует трейт LogsActivity");
                }
                if ($data['options'] === '❌') {
                    $this->line("  - Не имеет метода getActivitylogOptions");
                }
                if ($data['use_type'] === 'нет') {
                    $this->line("  - Нет use-директивы для LogsActivity");
                }
            }
        }
        
        // Рекомендации по исправлению
        $this->newLine();
        $this->info('🎯 РЕКОМЕНДАЦИИ ПО ДОБАВЛЕНИЮ ЛОГИРОВАНИЯ:');
        
        $priorities = ['высокий', 'средний', 'низкий'];
        
        foreach ($priorities as $priority) {
            $modelsWithout = array_filter($results, fn($r) => 
                $r['priority'] === $priority && $r['status'] === '❌'
            );
            
            if (count($modelsWithout) > 0) {
                $this->line("\n{$priority} приоритет:");
                foreach ($modelsWithout as $model => $data) {
                    $this->line("  • {$model} ({$data['category']})");
                    
                    // Генерируем пример кода для каждой модели
                    if ($data['log'] === '❌' || $data['options'] === '❌') {
                        $example = $this->generateLoggingExample($model);
                        $this->line("    Пример кода для добавления:");
                        $this->line($example);
                    }
                }
            }
        }
        
        // Инструкция по внедрению
        $this->newLine();
        $this->info('📝 ИНСТРУКЦИЯ ПО ВНЕДРЕНИЮ ЛОГИРОВАНИЯ:');
        $this->line("1. Для каждой модели из списка выше добавьте вверху файла:");
        $this->line("   use Spatie\\Activitylog\\Traits\\LogsActivity;");
        $this->line("");
        $this->line("2. В теле класса добавьте:");
        $this->line("   use LogsActivity;");
        $this->line("");
        $this->line("3. Добавьте метод:");
        $this->line("   public function getActivitylogOptions(): LogOptions");
        $this->line("   {");
        $this->line("       return LogOptions::defaults()");
        $this->line("           ->logOnly(['name', 'email', 'status']) // настройте поля");
        $this->line("           ->logOnlyDirty()");
        $this->line("           ->dontSubmitEmptyLogs();");
        $this->line("   }");
        
        return Command::SUCCESS;
    }
    
    private function generateLoggingExample(string $model): string
    {
        $commonFields = [
            'User' => ['name', 'email', 'phone', 'status'],
            'Candidate' => ['full_name', 'email', 'phone', 'status', 'position'],
            'Vacancy' => ['title', 'description', 'status', 'salary_from', 'salary_to'],
            'WorkRequest' => ['title', 'description', 'status', 'work_date'],
            'Shift' => ['start_time', 'end_time', 'status', 'total_amount'],
            'Expense' => ['name', 'amount', 'category_id', 'status'],
            'Project' => ['name', 'description', 'status'],
            'Contractor' => ['name', 'contact_person', 'email', 'phone'],
        ];
        
        $fields = $commonFields[$model] ?? ['name', 'status', 'description'];
        
        $fieldsString = implode("', '", $fields);
        
        return <<<EXAMPLE
    // Вверху файла:
    use Spatie\\Activitylog\\Traits\\LogsActivity;
    
    // В теле класса:
    use LogsActivity;
    
    // Метод в классе:
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['{$fieldsString}'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
EXAMPLE;
    }
}
