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
            'make_trainee_decision', // Принятие решения по стажеру
        ];

        // Разрешения для системы уведомлений
        $notificationPermissions = [
            'view_notifications',
            'view_own_notifications',
            'manage_notifications',
            'mark_notifications_read',
        ];

        // Создаем все разрешения
        foreach ($traineePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($notificationPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ==================== СОЗДАЕМ РОЛИ ====================

        // Роль Стажера
        $trainee = Role::firstOrCreate(['name' => 'trainee']);
        $trainee->syncPermissions([
            'view_own_notifications',
            'mark_notifications_read',
        ]);

        // Роль HR
        $hr = Role::firstOrCreate(['name' => 'hr']);
        $hr->syncPermissions([
            'view_any_trainee_requests',
            'view_trainee_request', 
            'approve_trainee_requests_hr',
            'view_trainees',
            'view_notifications',
            'mark_notifications_read',
        ]);

        // Роль Менеджера
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'view_any_trainee_requests',
            'view_trainee_request',
            'approve_trainee_requests_manager', 
            'view_trainees',
            'make_trainee_decision',
            'view_notifications',
            'mark_notifications_read',
        ]);

        // ==================== ОБНОВЛЯЕМ СУЩЕСТВУЮЩИЕ РОЛИ ====================

        // Dispatcher может создавать запросы на стажировку
        $dispatcher = Role::firstOrCreate(['name' => 'dispatcher']);
        $dispatcher->givePermissionTo([
            'create_trainee_requests',
            'view_own_trainee_requests',
            'view_trainee_request',
            'make_trainee_decision', // Может принимать решение по своим стажерам
            'view_own_notifications',
            'mark_notifications_read',
        ]);

        // Initiator может создавать запросы на стажировку
        $initiator = Role::firstOrCreate(['name' => 'initiator']);
        $initiator->givePermissionTo([
            'create_trainee_requests', 
            'view_own_trainee_requests',
            'view_trainee_request',
            'make_trainee_decision', // Может принимать решение по своим стажерам
            'view_own_notifications',
            'mark_notifications_read',
        ]);

        // Admin получает все разрешения
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        $this->command->info('✅ Роли и разрешения для системы стажеров созданы успешно!');
        $this->command->info('👥 Новые роли: trainee, hr, manager');
        $this->command->info('🔐 Разрешения настроены для dispatcher и initiator');
    }
}
