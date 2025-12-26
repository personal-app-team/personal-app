<?php
// database/seeders/RoleSeeder.php  
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    private array $roleNames = [
        'admin',
        'initiator',
        'dispatcher', 
        'executor',
        'contractor_admin',
        'contractor_dispatcher',
        'contractor_executor',
        'hr',
        'manager',
        'trainee',
    ];

    public function run(): void
    {
        $this->command->info('👥 Создание ролей для Filament Shield...');
        
        // Очищаем кэш разрешений
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Удаляем роль viewer если существует (она лишняя)
        if ($viewer = Role::where('name', 'viewer')->first()) {
            $viewer->delete();
            $this->command->info("🗑️ Роль 'viewer' удалена (лишняя в новой системе)");
        }
        
        // Создаем все роли БЕЗ поля description
        foreach ($this->roleNames as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName],
                [
                    'guard_name' => 'web',
                ]
            );
            $this->command->info("✅ Роль '{$roleName}' создана");
        }
        
        // Даем ВСЕ разрешения только admin (Shield супер-админ)
        $adminRole = Role::where('name', 'admin')->first();
        $allPermissions = Permission::all();
        
        if ($adminRole && $allPermissions->isNotEmpty()) {
            $adminRole->syncPermissions($allPermissions);
            $this->command->info("🎯 Роль 'admin' получила все разрешения ({$allPermissions->count()})");
            $this->command->info("🛡️  Теперь 'admin' - супер-админ Filament Shield");
        }
        
        $this->command->info('🎉 Роли созданы!');
        $this->command->info('💡 Настройте разрешения для других ролей через панель Shield');
        
        // Статистика
        $this->command->info("\n📊 Итоговая статистика:");
        foreach (Role::all() as $role) {
            $count = $role->permissions()->count();
            $this->command->info("  - {$role->name}: {$count} разрешений");
        }
    }
}