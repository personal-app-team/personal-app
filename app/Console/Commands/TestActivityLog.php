<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use Illuminate\Console\Command;

class TestActivityLog extends Command
{
    protected $signature = 'test:activity-log';
    protected $description = 'Тестирование системы логирования';
    
    public function handle()
    {
        $this->info('Тестирование логирования Assignment...');
        
        // Создаем тестовое назначение
        $assignment = Assignment::create([
            'user_id' => 1,
            'assignment_type' => 'work_request',
            'status' => 'pending',
            'planned_date' => now()->addDay(),
            'assignment_comment' => 'Тестовое назначение',
        ]);
        
        $this->info("Создано назначение ID: {$assignment->id}");
        
        // Изменяем назначение
        $assignment->update([
            'status' => 'confirmed',
            'assignment_comment' => 'Измененный комментарий',
        ]);
        
        $this->info("Изменено назначение ID: {$assignment->id}");
        
        // Проверяем логи
        $logs = $assignment->activities()->count();
        $this->info("Количество записей в логах: {$logs}");
        
        // Показываем логи
        $this->table(
            ['Действие', 'Свойства'],
            $assignment->activities->map(function ($log) {
                return [
                    $log->description,
                    json_encode($log->properties->toArray(), JSON_UNESCAPED_UNICODE)
                ];
            })
        );
        
        return Command::SUCCESS;
    }
}
