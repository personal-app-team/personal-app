<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class TestActivityLogIndexes extends Command
{
    protected $signature = 'logs:test-indexes';
    protected $description = 'Тестирование производительности индексов activity_log';

    public function handle()
    {
        $this->info('🔍 Тестирование индексов таблицы activity_log...');
        
        // 1. Проверка существующих индексов
        $this->info("\n1. Существующие индексы:");
        $indexes = DB::select('SHOW INDEXES FROM activity_log');
        
        $tableData = collect($indexes)->map(function ($index) {
            return [
                'Индекс' => $index->Key_name,
                'Колонки' => $index->Column_name,
                'Тип' => $index->Index_type,
                'Уникальность' => $index->Non_unique == 0 ? 'Да' : 'Нет',
            ];
        });
        
        $this->table(['Индекс', 'Колонки', 'Тип', 'Уникальность'], $tableData);
        
        // 2. Тест скорости запросов с индексами
        $this->info("\n2. Тест скорости запросов:");
        
        // Тест 1: Фильтрация по created_at
        $start = microtime(true);
        $count1 = Activity::whereDate('created_at', '>=', now()->subMonth())->count();
        $time1 = microtime(true) - $start;
        
        // Тест 2: Фильтрация по subject_type и event
        $start = microtime(true);
        $count2 = Activity::where('subject_type', 'App\\Models\\Shift')
                ->where('event', 'updated')
                ->count();
        $time2 = microtime(true) - $start;
        
        // Тест 3: Сортировка по created_at
        $start = microtime(true);
        $records = Activity::orderBy('created_at', 'desc')->limit(100)->get();
        $time3 = microtime(true) - $start;
        
        $this->table(['Запрос', 'Количество', 'Время (сек)'], [
            ['Фильтр по дате (последний месяц)', $count1, round($time1, 4)],
            ['Фильтр по типу и событию', $count2, round($time2, 4)],
            ['Сортировка по дате (100 записей)', $records->count(), round($time3, 4)],
        ]);
        
        // 3. Анализ производительности
        $this->info("\n3. Анализ производительности:");
        
        $totalRecords = Activity::count();
        $this->line("📊 Всего записей в таблице: {$totalRecords}");
        
        if ($totalRecords > 100000) {
            $this->warn("⚠️  Большое количество записей (>100k). Рекомендации:");
            $this->line("  • Включить партиционирование по месяцам");
            $this->line("  • Регулярно архивировать старые логи");
            $this->line("  • Рассмотреть переход на PostgreSQL для партиционирования");
        } elseif ($totalRecords > 50000) {
            $this->info("ℹ️  Среднее количество записей. Рекомендации:");
            $this->line("  • Продолжать регулярную очистку (ежедневно)");
            $this->line("  • Еженедельно оптимизировать таблицу");
        } else {
            $this->info("✅ Количество записей в норме. Производительность хорошая.");
        }
        
        // 4. Проверка использования индексов
        $this->info("\n4. Проверка использования индексов:");
        
        // Пример EXPLAIN запроса
        try {
            $explain = DB::select('EXPLAIN SELECT * FROM activity_log WHERE created_at >= ?', [now()->subMonth()->toDateTimeString()]);
            $this->line("План запроса по дате:");
            foreach ($explain as $row) {
                $this->line("  • type: {$row->type}, key: {$row->key}, rows: {$row->rows}");
            }
        } catch (\Exception $e) {
            $this->warn("Не удалось получить план запроса: " . $e->getMessage());
        }
        
        $this->info("\n✅ Тестирование завершено.");
        
        return 0;
    }
}
