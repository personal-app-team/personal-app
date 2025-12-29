<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Начало актуализации базы данных...');

        // Очищаем кэш перед началом
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Удаляем старые политики из неправильной папки
        $this->cleanupOldPolicies();

        // 1. Справочники
        $this->call(ContractTypeTaxStatusSeeder::class);
        $this->command->info('✅ Справочники договоров и налогов актуализированы');

        // 2. Категории и специальности
        $this->call(CategorySpecialtySeeder::class);
        $this->command->info('✅ Категории и специальности актуализированы');

        // 3. Виды работ
        $this->call(WorkTypeSeeder::class);
        $this->command->info('✅ Виды работ актуализированы');

        // 4. Создаем базовые роли (БЕЗ разрешений)
        $this->call(RoleSeeder::class);
        $this->command->info('✅ Базовые роли созданы');

        // 5. Актуализируем разрешения и восстанавливаем состояния ролей
        $this->call(PermissionSeeder::class);
        $this->command->info('✅ Разрешения сгенерированы и назначения восстановлены');

        // 6. Администратор системы
        $this->call(AdminSeeder::class);
        $this->command->info('✅ Администратор системы актуализирован');

        // 7. Тестовые пользователи с ролями
        $this->call(UserSeeder::class);
        $this->command->info('✅ Тестовые пользователи актуализированы');

        // Финальная очистка кэша
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info("\n🎉 База данных успешно актуализирована!");
        $this->command->info('🔐 Система использует Filament Shield для управления правами');
        $this->command->info('📁 Политики созданы в: app/Policies/');
    }
    
    private function cleanupOldPolicies(): void
    {
        $incorrectPath = base_path('app/var');
        if (file_exists($incorrectPath)) {
            // Удаляем рекурсивно
            $this->deleteDirectory($incorrectPath);
            $this->command->info('🗑️  Удалены старые политики из неправильной папки');
        }
    }
    
    private function deleteDirectory($path): bool
    {
        if (!file_exists($path)) {
            return true;
        }
        
        if (!is_dir($path)) {
            return unlink($path);
        }
        
        foreach (scandir($path) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            if (!$this->deleteDirectory($path . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        
        return rmdir($path);
    }
}
