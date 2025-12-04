<?php

namespace App\Console\Commands;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Console\Command;

class TestShiftActivityLog extends Command
{
    protected $signature = 'test:shift-activity-log';
    protected $description = 'Тестирование логирования смен';
    
    public function handle()
    {
        $this->info('Тестирование логирования Shift...');
        
        try {
            // Находим существующего пользователя
            $user = User::first();
            
            if (!$user) {
                $this->error('Нет пользователей в базе. Создайте пользователя.');
                return Command::FAILURE;
            }
            
            $this->info("Используем пользователя: {$user->full_name} (ID: {$user->id})");
            
            // Создаем тестовую смену
            $shift = Shift::create([
                'user_id' => $user->id,
                'work_date' => now()->format('Y-m-d'),
                'start_time' => '09:00',
                'status' => 'scheduled',
                'base_rate' => 500.00,
                'worked_minutes' => 480,
                'role' => 'executor',
            ]);
            
            $this->info("✅ Создана смена ID: {$shift->id}");
            
            // Изменяем финансовые данные
            $shift->update([
                'status' => 'completed',
                'compensation_amount' => 1000.00,
                'tax_amount' => 130.00,
                'payout_amount' => 1370.00,
                'is_paid' => true,
            ]);
            
            $this->info("✅ Изменена смена ID: {$shift->id}");
            
            // Проверяем логи
            $logs = $shift->activities()->count();
            $this->info("📊 Количество записей в логах: {$logs}");
            
            if ($logs > 0) {
                $this->info("📝 Последние записи:");
                $this->table(
                    ['Время', 'Действие', 'Измененные поля'],
                    $shift->activities()->latest()->take(3)->get()->map(function ($log) {
                        $changes = [];
                        if ($log->event === 'updated' && isset($log->properties['attributes'])) {
                            foreach ($log->properties['attributes'] as $key => $value) {
                                if (isset($log->properties['old'][$key]) && $log->properties['old'][$key] != $value) {
                                    $changes[] = $key;
                                }
                            }
                        }
                        return [
                            $log->created_at->format('H:i:s'),
                            $log->description,
                            implode(', ', $changes) ?: '—',
                        ];
                    })
                );
            }
            
            $this->info('✅ Тестирование завершено успешно!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Ошибка: ' . $e->getMessage());
            $this->error('Файл: ' . $e->getFile() . ':' . $e->getLine());
            $this->error('Трейс: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
