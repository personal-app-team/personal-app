<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FixPermissions extends Command
{
    protected $signature = 'permissions:fix';
    protected $description = 'Исправить права доступа для администратора';

    public function handle()
    {
        $this->info('🔧 Исправление прав доступа...');

        // 1. Проверяем пользователя admin
        $admin = User::where('email', 'admin@example.com')->first();
        
        if (!$admin) {
            $this->error('❌ Пользователь admin@example.com не найден');
            return;
        }

        $this->info("👤 Найден администратор: {$admin->email}");

        // 2. Проверяем и создаем роль admin если нужно
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );
        $this->info("🎭 Роль 'admin' проверена");

        // 3. Назначаем все разрешения роли admin
        $permissions = Permission::all();
        $adminRole->syncPermissions($permissions);
        $this->info("🔑 Роли 'admin' назначено " . $permissions->count() . " разрешений");

        // 4. Назначаем роль администратору
        $admin->syncRoles(['admin']);
        $this->info("👤 Пользователю {$admin->email} назначена роль admin");

        // 5. Проверяем результат
        $admin->refresh();
        $this->info("\n📊 Результат:");
        $this->info("  • Роли: " . $admin->roles->pluck('name')->implode(', '));
        $this->info("  • Разрешений: " . $admin->getAllPermissions()->count());
        
        // Проверяем доступ к ключевым ресурсам
        $resources = ['WorkRequest', 'User', 'Contractor', 'Shift', 'Assignment'];
        $this->info("\n🔍 Проверка доступа к ресурсам:");
        
        foreach ($resources as $resource) {
            $canView = $admin->can("view_any_{$resource}");
            $this->info(sprintf("  • %-20s: %s", $resource, $canView ? '✅' : '❌'));
        }

        $this->info("\n🎉 Права доступа исправлены!");
    }
}
