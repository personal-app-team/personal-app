<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Добавляет роли для подрядчиков и специфичные разрешения
     */
    public function up(): void
    {
        // 1. Создаем специфичные разрешения для подрядчиков
        $contractorPermissions = [
            // Управление пользователями своей компании
            'manage_contractor_users',
            'view_contractor_statistics',
            'approve_contractor_assignments',
            'create_contractor_work_request',
            'view_own_contractor_data',
            
            // Доступ к данным только своей компании
            'view_own_company_users',
            'view_own_company_assignments',
            'view_own_company_shifts',
            'view_own_company_expenses',
        ];

        foreach ($contractorPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }

        // 2. Создаем роли подрядчиков
        $contractorRoles = [
            [
                'name' => 'contractor_admin',
                'description' => 'Администратор подрядчика (управляющий компанией)',
            ],
            [
                'name' => 'contractor_dispatcher',
                'description' => 'Диспетчер подрядчика (управление заявками)',
            ],
            [
                'name' => 'contractor_executor',
                'description' => 'Исполнитель подрядчика (работа с заданиями)',
            ],
        ];

        foreach ($contractorRoles as $roleData) {
            Role::firstOrCreate([
                'name' => $roleData['name'],
                'guard_name' => 'web'
            ]);
        }

        // 3. Назначаем базовые разрешения для contractor_admin
        $contractorAdminRole = Role::where('name', 'contractor_admin')->first();
        if ($contractorAdminRole) {
            $adminPermissions = Permission::whereIn('name', [
                // Базовые CRUD операции для своей компании
                'view_any_user', 'view_user', 'create_user', 'update_user',
                'view_any_work_request', 'view_work_request', 'create_work_request', 'update_work_request',
                'view_any_assignment', 'view_assignment', 'create_assignment', 'update_assignment',
                'view_any_shift', 'view_shift', 'create_shift', 'update_shift',
                'view_any_expense', 'view_expense', 'create_expense', 'update_expense',
                'view_any_contractor', 'view_contractor', 'update_contractor',
                
                // Специфичные разрешения подрядчиков
                'manage_contractor_users',
                'view_contractor_statistics',
                'approve_contractor_assignments',
                'create_contractor_work_request',
                'view_own_contractor_data',
                'view_own_company_users',
                'view_own_company_assignments',
                'view_own_company_shifts',
                'view_own_company_expenses',
            ])->get();
            
            $contractorAdminRole->syncPermissions($adminPermissions);
        }
    }

    public function down(): void
    {
        // Удаляем роли подрядчиков (разрешения оставляем - могут использоваться другими ролями)
        Role::whereIn('name', ['contractor_admin', 'contractor_dispatcher', 'contractor_executor'])->delete();
    }
};
