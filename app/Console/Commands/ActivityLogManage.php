<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityLogManage extends Command
{
    protected $signature = 'activity:manage 
                            {action : Действие (cleanup|stats|optimize|archive)}
                            {--days=365 : Для cleanup - удалить логи старше X дней}
                            {--dry-run : Только показать, что будет сделано}
                            {--chunk=1000 : Размер чанка для пакетной обработки}';
    
    protected $description = 'Управление системой логов активности';

    public function handle()
    {
        $action = $this->argument('action');
        
        return match($action) {
            'cleanup' => $this->cleanup(),
            'stats' => $this->stats(),
            'optimize' => $this->optimize(),
            'archive' => $this->archive(),
            default => $this->error('Неизвестное действие. Используйте: cleanup, stats, optimize, archive'),
        };
    }
    
    protected function cleanup()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        $chunkSize = $this->option('chunk');
        
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("🧹 Очистка логов старше {$days} дней (до {$cutoffDate->format('d.m.Y')})...");
        
        $query = Activity::where('created_at', '<', $cutoffDate);
        $count = $query->count();
        
        if ($count === 0) {
            $this->info("✅ Нет записей для удаления.");
            return 0;
        }
        
        if ($dryRun) {
            $this->warn("⚠️  DRY RUN: Будет удалено {$count} записей логов.");
            $this->info("Последние 5 записей для удаления:");
            
            $query->orderBy('created_at', 'desc')->limit(5)->get()->each(function ($log) {
                $this->line("  • #{$log->id} - {$this->formatSubjectType($log->subject_type)} #{$log->subject_id} - {$log->created_at->format('d.m.Y H:i')}");
            });
            
            return 0;
        }
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        $deleted = 0;
        $query->chunkById($chunkSize, function ($logs) use (&$deleted, $bar) {
            Activity::whereIn('id', $logs->pluck('id'))->delete();
            $deleted += $logs->count();
            $bar->advance($logs->count());
        });
        
        $bar->finish();
        $this->newLine();
        
        $this->info("✅ Успешно удалено {$deleted} записей.");
        
        Log::channel('activity')->info('Очищены старые логи', [
            'deleted_count' => $deleted,
            'days' => $days,
        ]);
        
        return 0;
    }
    
    protected function stats()
    {
        $this->info("📊 Статистика системы логов:");
        $this->newLine();
        
        // Общая статистика
        $total = Activity::count();
        $today = Activity::whereDate('created_at', Carbon::today())->count();
        $yesterday = Activity::whereDate('created_at', Carbon::yesterday())->count();
        $last7days = Activity::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        
        $this->table(
            ['Показатель', 'Значение'],
            [
                ['Всего записей', $total],
                ['Сегодня', $today],
                ['Вчера', $yesterday],
                ['За последние 7 дней', $last7days],
            ]
        );
        
        // Статистика по типам объектов
        $this->info("\n📁 Статистика по типам объектов:");
        
        $bySubject = Activity::select('subject_type', DB::raw('count(*) as count'))
            ->whereNotNull('subject_type')
            ->groupBy('subject_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        $tableData = $bySubject->map(function ($item) {
            return [
                'Тип объекта' => $this->formatSubjectType($item->subject_type),
                'Количество' => $item->count,
            ];
        })->toArray();
        
        $this->table(['Тип объекта', 'Количество'], $tableData);
        
        // Статистика по событиям
        $this->info("\n🎯 Статистика по событиям:");
        
        $byEvent = Activity::select('event', DB::raw('count(*) as count'))
            ->whereNotNull('event')
            ->groupBy('event')
            ->orderBy('count', 'desc')
            ->get();
        
        $eventData = $byEvent->map(function ($item) {
            return [
                'Событие' => $this->formatEvent($item->event),
                'Количество' => $item->count,
            ];
        })->toArray();
        
        $this->table(['Событие', 'Количество'], $eventData);
        
        return 0;
    }
    
    protected function optimize()
    {
        $this->info("⚙️  Оптимизация таблицы activity_log...");
        
        if (config('database.default') === 'mysql') {
            DB::statement('OPTIMIZE TABLE activity_log');
            $this->info("✅ Таблица оптимизирована.");
        } else {
            $this->warn("⚠️  Оптимизация таблицы доступна только для MySQL.");
        }
        
        return 0;
    }
    
    protected function archive()
    {
        $this->info("📦 Архивация логов (заглушка - функция в разработке)");
        $this->line("В будущем здесь будет архивация логов в S3 или файловую систему.");
        return 0;
    }
    
    private function formatSubjectType($type): string
    {
        return match($type) {
            'App\\Models\\Assignment' => '📋 Назначение',
            'App\\Models\\Shift' => '💰 Смена',
            'App\\Models\\WorkRequest' => '📄 Заявка',
            'App\\Models\\User' => '👤 Пользователь',
            'App\\Models\\Compensation' => '💸 Компенсация',
            'App\\Models\\Candidate' => '👨‍💼 Кандидат',
            default => class_basename($type),
        };
    }
    
    private function formatEvent($event): string
    {
        return match($event) {
            'created' => 'Создание',
            'updated' => 'Изменение',
            'deleted' => 'Удаление',
            'restored' => 'Восстановление',
            default => $event,
        };
    }
}
