<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Начало актуализации базы данных...');

        // Очищаем кэш перед началом
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Удаляем старые политики из неправильной папки (если есть)
        $this->cleanupOldPolicies();

        // 1. Справочники
        $this->call(ContractTypeTaxStatusSeeder::class);
        $this->command->info('✅ Справочники договоров и налогов актуализированы');

        // 1.1. Шаблоны адресов
        $this->call(AddressSeeder::class);
        $this->command->info('✅ Адреса созданы');

        // 1.2. Проект "ЗИМА В МОСКВЕ 25"
        $this->call(ProjectSeeder::class);
        $this->command->info('✅ Проект "ЗИМА В МОСКВЕ 25" создан');

        // 2. Категории и специальности
        $this->call(CategorySpecialtySeeder::class);
        $this->command->info('✅ Категории и специальности актуализированы');

        // 3. Виды работ
        $this->call(WorkTypeSeeder::class);
        $this->command->info('✅ Виды работ актуализированы');

        // 4. Создаем базовые роли (БЕЗ разрешений)
        $this->call(RoleSeeder::class);
        $this->command->info('✅ Базовые роли созданы');

        // 5. Актуализируем разрешения и политики
        $this->call(PermissionSeeder::class);
        $this->command->info('✅ Разрешения и политики актуализированы');

        // 6. Администратор системы
        $this->call(AdminSeeder::class);
        $this->command->info('✅ Администратор системы актуализирован');

        // 7. Тестовые пользователи с ролями
        $this->call(UserSeeder::class);
        $this->command->info('✅ Тестовые пользователи актуализированы');

        // Финальная очистка кэша
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info("\n🎉 База данных успешно актуализирована!");
        $this->command->info('🔐 Политики сохранены в правильной папке: app/Policies/');
    }
    
    private function cleanupOldPolicies(): void
    {
        $incorrectPath = base_path('app/var');
        
        if (file_exists($incorrectPath)) {
            $this->command->info('🗑️  Удаляем старые политики из неправильной папки...');
            
            try {
                // Для WSL/Docker используем system call
                exec('rm -rf ' . escapeshellarg($incorrectPath) . ' 2>/dev/null', $output, $returnCode);
                
                if ($returnCode === 0) {
                    $this->command->info('✅ Папка удалена: ' . $incorrectPath);
                } else {
                    // Альтернативный способ через File facade
                    File::deleteDirectory($incorrectPath);
                    $this->command->info('✅ Папка удалена через File facade');
                }
            } catch (\Exception $e) {
                $this->command->warn('⚠️  Не удалось удалить папку автоматически. Удалите вручную:');
                $this->command->line('   rm -rf app/var');
            }
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
