<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    // Только создание базовых ролей, без назначения разрешений
    // Назначениями займется PermissionSeeder
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
    
    // Паттерны для ролей (используются только при первом создании или принудительном обновлении)
    // В нормальной работе PermissionSeeder сам восстановит состояния
    private array $rolePermissionPatterns = [
        'initiator' => ['workrequest', 'traineerequest', 'recruitmentrequest'],
        'dispatcher' => ['assignment', 'workrequest', 'shift'],
        'executor' => ['shift', 'expense', 'assignment'],
        'hr' => ['vacancy', 'recruitmentrequest', 'candidate', 'interview', 'traineerequest'],
        'manager' => ['hiringdecision', 'positionchangerequest', 'traineerequest'],
        'contractor_admin' => ['contractor', 'contractorrate', 'workrequest'],
        'contractor_dispatcher' => ['assignment', 'workrequest'],
        'contractor_executor' => ['shift', 'expense', 'assignment'],
        'trainee' => ['shift', 'assignment'],
    ];

    public function run(): void
    {
        $this->command->info('👥 Создание базовых ролей...');

        // Очищаем кэш разрешений
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Создаем только роли без разрешений
        foreach ($this->roleNames as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'web']
            );
            $this->command->info("✅ Роль '{$roleName}' создана");
        }

        $this->command->info("\n💡 Назначения разрешений ролям будут выполнены в PermissionSeeder");
        $this->command->info("   Для принудительной настройки выполните:");
        $this->command->info("   php artisan db:seed --class=RoleSeeder --force-setup");
        
        // Если есть аргумент --force-setup, устанавливаем базовые разрешения
        if (in_array('--force-setup', $_SERVER['argv'] ?? [])) {
            $this->setupBasicPermissions();
        }
    }
    
    private function setupBasicPermissions(): void
    {
        $this->command->info("\n🔧 Принудительная настройка базовых разрешений...");
        
        foreach ($this->rolePermissionPatterns as $roleName => $patterns) {
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                $this->command->warn("⚠️ Роль '{$roleName}' не найдена");
                continue;
            }
            
            $permissions = collect();
            
            foreach ($patterns as $pattern) {
                $foundPermissions = Permission::where('name', 'like', "%{$pattern}%")->get();
                $permissions = $permissions->merge($foundPermissions);
            }
            
            // Убираем дубликаты
            $permissions = $permissions->unique('id');
            
            $role->syncPermissions($permissions);
            
            $this->command->info("✅ Роли '{$roleName}' назначено {$permissions->count()} разрешений");
        }
        
        // Admin получает все разрешения
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $allPermissions = Permission::all();
            $adminRole->syncPermissions($allPermissions);
            $this->command->info("🎯 Роль 'admin' получила все разрешения ({$allPermissions->count()})");
        }
    }
}
