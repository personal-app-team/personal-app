<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🛡️  Начинаем актуализацию разрешений...');

        // 1. Сохраняем текущие состояния разрешений
        $oldPermissionNames = Permission::pluck('name')->toArray();

        // 2. Сохраняем текущие состояния ролей
        $roleStates = $this->saveRoleStates();

        // 3. Генерируем разрешения через Shield с исправлением путей
        $this->command->info('📋 Запуск исправленной генерации политик...');
        
        // Сохраняем существующие политики, если есть
        $existingPolicies = $this->backupExistingPolicies();
        
        // Используем нашу кастомную команду
        Artisan::call('shield:generate-correct');
        $this->command->info('✅ Политики сгенерированы и пути исправлены');

        // 4. Восстанавливаем политики, которые были изменены вручную
        $this->restoreManualPolicies($existingPolicies);

        // 5. Определяем новые разрешения
        $newPermissions = Permission::whereNotIn('name', $oldPermissionNames)->get();

        if ($newPermissions->count() > 0) {
            $this->command->info("\n🔔 ВНИМАНИЕ: Обнаружены новые разрешения!");
            $this->command->info("   Новые разрешения были автоматически добавлены только для роли 'admin'.");
            $this->command->info("   Для других ролей назначьте их вручную через панель Shield.");

            $this->command->info("\n📋 Список новых разрешений:");
            foreach ($newPermissions as $permission) {
                $this->command->info("   • {$permission->name}");
            }
        } else {
            $this->command->info("\n✅ Новых разрешений не обнаружено.");
        }

        // 6. Восстанавливаем состояния ролей
        $this->restoreRoleStates($roleStates);

        // 7. Админу все разрешения
        $this->giveAdminAllPermissions();

        // 8. Статистика
        $this->showStatistics();
    }

    private function saveRoleStates(): array
    {
        $roleStates = [];

        $roles = Role::with('permissions')->get();

        foreach ($roles as $role) {
            $roleStates[$role->name] = $role->permissions->pluck('name')->toArray();
        }

        return $roleStates;
    }

    private function backupExistingPolicies(): array
    {
        $policyPath = base_path('app/Policies');
        $backup = [];
        
        if (File::exists($policyPath)) {
            $files = File::files($policyPath);
            
            foreach ($files as $file) {
                $filename = $file->getFilename();
                $backup[$filename] = File::get($file->getPathname());
            }
        }
        
        return $backup;
    }

    private function restoreManualPolicies(array $existingPolicies): void
    {
        $policyPath = base_path('app/Policies');
        
        // Список политик, которые мы изменяли вручную
        $manualPolicies = [
            'AssignmentPolicy.php',
            // Добавьте другие политики, которые изменяли вручную
        ];
        
        foreach ($manualPolicies as $policy) {
            if (isset($existingPolicies[$policy])) {
                File::put($policyPath . '/' . $policy, $existingPolicies[$policy]);
                $this->command->info("✅ Восстановлена ручная политика: {$policy}");
            }
        }
    }

    private function restoreRoleStates(array $roleStates): void
    {
        $this->command->info('🔄 Восстанавливаем сохраненные назначения ролей...');

        foreach ($roleStates as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();

            if (!$role || $roleName === 'admin') continue;

            $permissions = Permission::whereIn('name', $permissionNames)->get();
            $role->syncPermissions($permissions);

            $this->command->info("✅ Роли '{$roleName}' восстановлено {$permissions->count()} разрешений");
        }
    }

    private function giveAdminAllPermissions(): void
    {
        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) return;

        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);

        $this->command->info("🎯 Роль 'admin' получила все разрешения ({$allPermissions->count()})");
    }

    private function showStatistics(): void
    {
        $this->command->info("\n📊 Итоговая статистика:");

        foreach (Role::withCount('permissions')->orderBy('name')->get() as $role) {
            $this->command->info("   - {$role->name}: {$role->permissions_count} разрешений");
        }
    }
}
