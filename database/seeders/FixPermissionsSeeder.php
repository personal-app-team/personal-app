<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class FixPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🧹 Очистка кэша разрешений...');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('🔄 Создание отсутствующих разрешений для Filament...');
        
        // Список всех ресурсов Filament
        $resources = [
            'User', 'Role', 'Contractor', 'WorkRequest', 'Assignment', 'Shift',
            'Category', 'Specialty', 'WorkType', 'ContractorRate', 'Expense',
            'Compensation', 'MassPersonnelReport', 'TraineeRequest', 'Department',
            'EmploymentHistory', 'Vacancy', 'VacancyTask', 'VacancyRequirement',
            'VacancyCondition', 'RecruitmentRequest', 'Candidate', 'CandidateStatusHistory',
            'CandidateDecision', 'Interview', 'HiringDecision', 'PositionChangeRequest',
            'Project', 'Purpose', 'PurposeTemplate', 'Address', 'AddressTemplate',
            'PurposePayerCompany', 'PurposeAddressRule', 'ContractType', 'TaxStatus',
            'ActivityLog', 'Photo', 'VisitedLocation', 'WorkRequestStatus', 'InitiatorGrant',
            'ContractorWorker'
        ];

        // Базовые действия для ресурсов
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];

        $createdPermissions = [];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissionName = $action . '_' . strtolower($resource);
                
                if (!Permission::where('name', $permissionName)->exists()) {
                    Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
                    $createdPermissions[] = $permissionName;
                }
            }
        }

        // Специальные разрешения
        $specialPermissions = [
            'access_panel',
            'export_data',
            'import_data',
            'manage_settings',
            'view_reports',
            'approve_shifts',
            'approve_expenses',
            'manage_payments',
            'assign_roles',
            'manage_permissions',
        ];

        foreach ($specialPermissions as $permission) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission, 'guard_name' => 'web']);
                $createdPermissions[] = $permission;
            }
        }

        if (!empty($createdPermissions)) {
            $this->command->info('✅ Созданы разрешения для ресурсов');
            $this->command->info('📝 Создано разрешений: ' . count($createdPermissions));
        } else {
            $this->command->info('📝 Все разрешения уже существуют');
        }

        // Создаем базовые роли
        $roles = ['admin', 'initiator', 'dispatcher', 'executor', 'hr', 'manager', 'contractor_admin', 'contractor_dispatcher', 'contractor_executor', 'trainee', 'viewer'];
        
        foreach ($roles as $roleName) {
            if (!Role::where('name', $roleName)->exists()) {
                Role::create(['name' => $roleName, 'guard_name' => 'web']);
                $this->command->info("✅ Роль '{$roleName}' создана");
            }
        }

        // Назначаем ВСЕ разрешения роли admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $allPermissions = Permission::all()->pluck('name')->toArray();
            $adminRole->syncPermissions($allPermissions);
            $this->command->info('👑 Админу назначены все разрешения (' . count($allPermissions) . ')');
        }

        $this->command->info('🎉 Разрешения и роли обновлены!');
    }
}
