<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class DebugActivityLog extends Command
{
    protected $signature = 'debug:activity-log';
    protected $description = 'Отладка системы логов ActivityLog';

    public function handle()
    {
        $this->info('=== ОТЛАДКА СИСТЕМЫ ЛОГОВ ===');
        
        // 1. Общая статистика
        $total = Activity::count();
        $this->info("Всего записей в логах: {$total}");
        
        // 2. Проверка условия now()->subYear()
        $oneYearAgo = now()->subYear();
        $this->info("Дата 'год назад' (now()->subYear()): " . $oneYearAgo->format('Y-m-d H:i:s'));
        $this->info("Часовой пояс: " . $oneYearAgo->timezone->getName());
        
        $withCondition = Activity::where('created_at', '>=', $oneYearAgo)->count();
        $this->info("Записей по условию (created_at >= 'год назад'): {$withCondition}");
        
        // 3. Проверка гдеDate
        $withWhereDate = Activity::whereDate('created_at', '>=', $oneYearAgo)->count();
        $this->info("Записей по условию whereDate: {$withWhereDate}");
        
        // 4. Проверка с явным указанием начала дня
        $oneYearAgoStart = Carbon::now()->subYear()->startOfDay();
        $this->info("Дата 'год назад начало дня': " . $oneYearAgoStart->format('Y-m-d H:i:s'));
        
        $withStartOfDay = Activity::where('created_at', '>=', $oneYearAgoStart)->count();
        $this->info("Записей по условию (>= startOfDay): {$withStartOfDay}");
        
        // 5. Проверим UTC
        $oneYearAgoUTC = Carbon::now('UTC')->subYear()->startOfDay();
        $this->info("Дата 'год назад UTC': " . $oneYearAgoUTC->format('Y-m-d H:i:s'));
        
        $withUTC = Activity::where('created_at', '>=', $oneYearAgoUTC)->count();
        $this->info("Записей по условию (UTC): {$withUTC}");
        
        // 6. Выведем несколько примеров записей
        $this->info("\n=== ПОСЛЕДНИЕ 5 ЗАПИСЕЙ ===");
        $recent = Activity::orderBy('created_at', 'desc')->take(5)->get();
        
        $this->table(
            ['ID', 'Дата создания', 'Тип', 'Действие', 'Создано'],
            $recent->map(function($log) {
                return [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    class_basename($log->subject_type),
                    $log->description,
                    $log->created_at->diffForHumans()
                ];
            })
        );
        
        // 7. Проверим самый старый и самый новый лог
        $oldest = Activity::orderBy('created_at', 'asc')->first();
        $newest = Activity::orderBy('created_at', 'desc')->first();
        
        if ($oldest && $newest) {
            $this->info("\nСамый старый лог: " . $oldest->created_at->format('Y-m-d H:i:s'));
            $this->info("Самый новый лог: " . $newest->created_at->format('Y-m-d H:i:s'));
        }
        
        return Command::SUCCESS;
    }
}
