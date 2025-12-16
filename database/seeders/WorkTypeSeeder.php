<?php

namespace Database\Seeders;

use App\Models\WorkType;
use Illuminate\Database\Seeder;

class WorkTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔧 Создание видов работ...');
        
        $workTypes = [
            ['name' => 'Монтаж', 'description' => 'Монтажные работы'],
            ['name' => 'Демонтаж', 'description' => 'Демонтажные работы'],
            ['name' => 'Ремонт', 'description' => 'Ремонтные работы'],
            ['name' => 'Обслуживание', 'description' => 'Техническое обслуживание'],
            ['name' => 'Установка', 'description' => 'Установочные работы'],
            ['name' => 'Настройка', 'description' => 'Пуско-наладочные работы'],
            ['name' => 'Контроль', 'description' => 'Контроль качества'],
            ['name' => 'Инспекция', 'description' => 'Техническая инспекция'],
        ];
        
        foreach ($workTypes as $workType) {
            WorkType::create($workType);
            $this->command->info("✅ Вид работ: {$workType['name']}");
        }
        
        $this->command->info('🎉 Виды работ созданы!');
    }
}
