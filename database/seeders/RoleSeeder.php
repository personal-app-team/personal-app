<?php
// database/seeders/RoleSeeder.php - ЗАМЕНИ весь файл этим кодом

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Базовые роли системы (ВСЕ 11 ролей которые есть в БД)
     */
    private array $roles = [
        'admin' => [
            'permissions' => 'all', // все разрешения
        ],
        'initiator' => [
            'permissions' => [
                'view_any_work_request',
                'view_work_request',
                'create_work_request',
                'update_work_request',
                'view_any_trainee_request',
                'create_trainee_request',
                'view_any_recruitment_request',
                'create_recruitment_request',
            ],
        ],
        'dispatcher' => [
            'permissions' => [
                'view_any_work_request',
                'view_work_request',
                'update_work_request',
                // 'take_work_request', // УДАЛИЛИ - такого разрешения нет
                'view_any_assignment',
                'create_assignment',
                'update_assignment',
                'view_any_shift',
                'view_shift',
                'view_any_user',
                'view_user',
            ],
        ],
        'executor' => [
            'permissions' => [
                'view_shift',
                'create_shift',
                'update_shift',
                'view_expense',
                'create_expense',
            ],
        ],
        'hr' => [
            'permissions' => [
                'view_any_vacancy',
                'create_vacancy',
                'update_vacancy',
                'view_any_candidate',
                'create_candidate',
                'update_candidate',
                'view_any_interview',
                'create_interview',
                'update_interview',
            ],
        ],
        'manager' => [
            'permissions' => [
                'view_any_hiring_decision',
                'create_hiring_decision',
                'update_hiring_decision',
                'view_any_position_change_request',
                'update_position_change_request',
                'view_any_trainee_request',
                'update_trainee_request',
            ],
        ],
        'contractor_admin' => [
            'permissions' => [
                'view_own_company_assignments',
                'view_own_company_expenses',
                'view_own_company_shifts',
                'view_own_company_users',
                'view_contractor_statistics',
            ],
        ],
        'contractor_dispatcher' => [
            'permissions' => [
                'view_own_company_assignments',
                'view_own_company_shifts',
                'view_own_company_users',
            ],
        ],
        'contractor_executor' => [
            'permissions' => [
                'view_shift',
                'create_shift',
                'view_expense',
                'create_expense',
            ],
        ],
        'trainee' => [
            'permissions' => [
                'view_shift',
                'view_work_request',
            ],
        ],
        'viewer' => [
            'permissions' => [
                'view_any_work_request',
                'view_work_request',
                'view_any_user',
                'view_user',
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('👥 Безопасное обновление ролей...');
        
        // Получаем все существующие разрешения
        $allPermissionNames = Permission::all()->pluck('name')->toArray();
        
        foreach ($this->roles as $roleName => $roleData) {
            // Находим или создаем роль
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['guard_name' => 'web']
            );
            
            $currentPermissions = $role->permissions->pluck('name')->toArray();
            
            if ($roleData['permissions'] === 'all') {
                // Для admin: даем все разрешения, которых еще нет
                $missingPermissions = array_diff($allPermissionNames, $currentPermissions);
                
                if (!empty($missingPermissions)) {
                    $role->givePermissionTo($missingPermissions);
                    $this->command->info("✅ Роль '{$roleName}' получила недостающие разрешения: " . count($missingPermissions));
                } else {
                    $this->command->info("⏭️  Роль '{$roleName}' уже имеет все разрешения (" . count($currentPermissions) . ")");
                }
            } elseif (is_array($roleData['permissions'])) {
                // Проверяем, какие разрешения из списка существуют
                $existingPermissions = array_intersect($roleData['permissions'], $allPermissionNames);
                $nonExistingPermissions = array_diff($roleData['permissions'], $allPermissionNames);
                
                // Показываем предупреждение о несуществующих разрешениях
                if (!empty($nonExistingPermissions)) {
                    $this->command->warn("⚠️  Для роли '{$roleName}' не существуют разрешения: " . implode(', ', $nonExistingPermissions));
                }
                
                // Добавляем только существующие разрешения, которых у роли еще нет
                $missingPermissions = array_diff($existingPermissions, $currentPermissions);
                
                if (!empty($missingPermissions)) {
                    $role->givePermissionTo($missingPermissions);
                    $this->command->info("✅ Роль '{$roleName}' получила недостающие разрешения: " . count($missingPermissions));
                } else {
                    $this->command->info("⏭️  Роль '{$roleName}' уже имеет все разрешения (" . count($currentPermissions) . ")");
                }
            }
        }
        
        $this->command->info('🎉 Роли безопасно обновлены!');
        
        // Финальная статистика
        $this->command->info("\n📊 Итоговая статистика:");
        foreach (Role::all() as $role) {
            $count = $role->permissions()->count();
            $this->command->info("  - {$role->name}: {$count} разрешений");
        }
    }
}
