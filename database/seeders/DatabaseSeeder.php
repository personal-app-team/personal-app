<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Начало заполнения базы данных...');
        
        // 1. Роли и разрешения (сначала!)
        $this->call(FixAllPermissionsSeeder::class);
        $this->command->info('✅ Роли и разрешения созданы');
        
        // 2. Справочники (договоры и налоги)
        $this->call(ContractTypeTaxStatusSeeder::class);
        $this->command->info('✅ Справочники договоров и налогов созданы');
        
        // 3. Администратор системы
        $this->call(AdminSeeder::class);
        $this->command->info('✅ Администратор системы создан');
        
        // 4. Тестовые пользователи с ролями
        $this->call(UserSeeder::class);
        $this->command->info('✅ Тестовые пользователи созданы');
        
        $this->command->info('🎉 База данных успешно заполнена!');
    }
}
