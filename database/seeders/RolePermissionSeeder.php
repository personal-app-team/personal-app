<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ==================== СОЗДАЕМ РАЗРЕШЕНИЯ ====================

        // Разрешения для системы стажеров
        $traineePermissions = [
            // Создание и просмотр запросов на стажировку
            'create_trainee_requests',
            'view_any_trainee_requests', 
            'view_own_trainee_requests',
            'view_trainee_request',
            'update_trainee_request',
            'delete_trainee_request',
            
            // Утверждение запросов
            'approve_trainee_requests_hr',
            'approve_trainee_requests_manager',
            'manage_trainee_requests',
            
            // Управление стажерами
            'view_trainees',
            'manage_trainees',
            'make_trainee_decision',
        ];

        // Разрешения для системы назначений
        $assignmentPermissions = [
            'create_brigadier_schedule',
            'create_work_request_assignment',
            'create_mass_personnel_assignment',
            'edit_assignments',
            'delete_assignments',
            'cancel_assignments',
        ];

        // Создаем все разрешения
        foreach ($traineePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($assignmentPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ==================== СОЗДАЕМ РОЛИ ====================

        // Роль Стажера
        $trainee = Role::firstOrCreate(['name' => 'trainee']);
        $trainee->syncPermissions([
            // Стажер имеет минимальные права
        ]);

        // Роль HR
        $hr = Role::firstOrCreate(['name' => 'hr']);
        $hr->syncPermissions([
            'view_any_trainee_requests',
            'view_trainee_request', 
            'approve_trainee_requests_hr',
            'view_trainees',
        ]);

        // Роль Менеджера
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'view_any_trainee_requests',
            'view_trainee_request',
            'approve_trainee_requests_manager', 
            'view_trainees',
            'make_trainee_decision',
        ]);

        // ==================== ОБНОВЛЯЕМ СУЩЕСТВУЮЩИЕ РОЛИ ====================

        // Dispatcher
        $dispatcher = Role::firstOrCreate(['name' => 'dispatcher']);
        $dispatcher->givePermissionTo([
            // Стажеры
            'create_trainee_requests',
            'view_own_trainee_requests',
            'view_trainee_request',
            'make_trainee_decision',
            
            // Назначения
            'create_work_request_assignment',
            'create_mass_personnel_assignment',
            'edit_assignments',
            'cancel_assignments',
        ]);

        // Initiator
        $initiator = Role::firstOrCreate(['name' => 'initiator']);
        $initiator->givePermissionTo([
            // Стажеры
            'create_trainee_requests', 
            'view_own_trainee_requests',
            'view_trainee_request',
            'make_trainee_decision',
            
            // Назначения
            'create_brigadier_schedule',
            'cancel_assignments',
        ]);

        // Executor - базовые права
        $executor = Role::firstOrCreate(['name' => 'executor']);
        $executor->givePermissionTo([
            // Базовые права исполнителя
        ]);

        // Contractor - базовые права  
        $contractor = Role::firstOrCreate(['name' => 'contractor']);
        $contractor->givePermissionTo([
            // Базовые права подрядчика
        ]);

        // Admin получает все разрешения
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        $this->command->info('✅ Роли и разрешения созданы успешно!');
        $this->command->info('👥 Роли: admin, dispatcher, initiator, executor, contractor, trainee, hr, manager');
        $this->command->info('🔐 Разрешения для назначений и стажеров настроены');
        $this->command->info('🗑️ Удалены разрешения для кастомных уведомлений');
    }
}
