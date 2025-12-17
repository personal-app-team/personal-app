<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Начало заполнения базы данных...');
        
        // 1. Роли и разрешения (сначала создаем только роли и разрешения)
        $this->call([
            \Database\Seeders\FixPermissionsSeeder::class, // НОВЫЙ сидер - только роли и разрешения
        ]);
        $this->command->info('✅ Роли и разрешения созданы');
        
        // 2. Справочники
        $this->call(ContractTypeTaxStatusSeeder::class);
        $this->command->info('✅ Справочники договоров и налогов созданы');
        
        // 3. Администратор системы (сразу получает роль admin)
        $this->call(AdminSeeder::class);
        $this->command->info('✅ Администратор системы создан');
        
        // 4. Категории и специальности
        $this->call(CategorySpecialtySeeder::class);
        $this->command->info('✅ Категории и специальности созданы');
        
        // 5. Виды работ
        $this->call(WorkTypeSeeder::class);
        $this->command->info('✅ Виды работ созданы');
        
        // 6. Тестовые пользователи с ролями
        $this->call(UserSeeder::class);
        $this->command->info('✅ Тестовые пользователи созданы');
        
        $this->command->info('🎉 База данных успешно заполнена!');
    }
}
