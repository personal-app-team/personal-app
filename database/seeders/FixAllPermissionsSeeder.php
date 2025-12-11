<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class FixAllPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Очистить кэш разрешений
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        
        $this->command->info('🧹 Очистка кэша разрешений...');
        
        // Удалить все существующие разрешения и роли (кроме admin)
        Permission::query()->delete();
        Role::whereNotIn('name', ['admin'])->delete();
        
        $this->command->info('🔄 Создание полного набора разрешений для Filament...');
        
        // ==================== СОЗДАЕМ ВСЕ РАЗРЕШЕНИЯ ====================
        
        // 1. Базовые разрешения для всех ресурсов
        $resources = [
            'activity_log', 'address', 'address_template', 'assignment', 'candidate',
            'candidate_decision', 'candidate_status_history', 'category', 'compensation', 
            'contract_type', 'contractor', 'contractor_rate', 'contractor_worker', 'department', 
            'employment_history', 'expense', 'hiring_decision', 'initiator_grant', 'interview', 
            'mass_personnel_report', 'photo', 'position_change_request', 'project', 'purpose', 
            'purpose_address_rule', 'purpose_payer_company', 'purpose_template', 'recruitment_request', 
            'role', 'shift', 'specialty', 'tax_status', 'trainee_request', 'user', 'vacancy',
            'vacancy_condition', 'vacancy_requirement', 'vacancy_task', 'visited_location',
            'work_request', 'work_request_status', 'work_type'
        ];
        
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];
        
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => $action . '_' . $resource,
                    'guard_name' => 'web'
                ]);
            }
        }
        
        $this->command->info('✅ Создано ' . (count($resources) * count($actions)) . ' разрешений для ресурсов');
        
        // 2. Специальные разрешения (ВСЕ, включая assign_executors)
        $specialPermissions = [
            // Системные
            'access_filament',
            'impersonate_users',
            
            // Workflow
            'approve_assignments',
            'reject_assignments',
            'confirm_assignments',
            'complete_assignments',
            'start_shifts',
            'end_shifts',
            'approve_shifts',
            'reject_shifts',
            'publish_work_requests',
            
            // Назначения и диспетчеризация
            'create_brigadier_schedule',
            'create_work_request_assignment',
            'create_mass_personnel_assignment',
            'edit_assignments',
            'cancel_assignments',
            'assign_executors',  // ЭТО РАЗРЕШЕНИЕ БЫЛО ПРОПУЩЕНО
            
            // Подбор персонала
            'assign_hr_to_recruitment',
            'make_candidate_decision',
            'schedule_interview',
            'make_hiring_decision',
            'approve_position_change',
            
            // Стажеры
            'approve_trainee_hr',
            'approve_trainee_manager',
            'activate_trainee',
            'complete_trainee',
            
            // Массовый персонал
            'generate_mass_report',
            'approve_mass_report',
            'pay_mass_report',
        ];
        
        foreach ($specialPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
        
        $this->command->info('✅ Создано ' . count($specialPermissions) . ' специальных разрешений');
        
        // ==================== СОЗДАЕМ РОЛИ ====================
        
        $roles = [
            'admin' => 'Администратор (полный доступ)',
            'hr' => 'HR-специалист',
            'manager' => 'Руководитель',
            'dispatcher' => 'Диспетчер',
            'initiator' => 'Инициатор',
            'executor' => 'Исполнитель',
            'contractor' => 'Подрядчик',
            'trainee' => 'Стажер',
            'viewer' => 'Наблюдатель',
        ];
        
        foreach ($roles as $name => $description) {
            Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web'
            ]);
        }
        
        $this->command->info('✅ Создано ' . count($roles) . ' ролей');
        
        // ==================== НАЗНАЧАЕМ РАЗРЕШЕНИЯ РОЛЯМ ====================
        
        // 1. Admin - все разрешения
        $adminRole = Role::where('name', 'admin')->first();
        $adminRole->syncPermissions(Permission::all());
        $this->command->info('👑 Роли admin назначены ВСЕ разрешения');
        
        // 2. HR - разрешения для подбора персонала
        $hrRole = Role::where('name', 'hr')->first();
        $hrPermissions = [];
        foreach ($resources as $resource) {
            if (in_array($resource, ['candidate', 'recruitment_request', 'interview', 'vacancy', 
                'vacancy_condition', 'vacancy_requirement', 'vacancy_task', 'trainee_request'])) {
                $hrPermissions = array_merge($hrPermissions, [
                    'view_any_' . $resource,
                    'view_' . $resource,
                    'create_' . $resource,
                    'update_' . $resource,
                ]);
            }
        }
        $hrPermissions = array_merge($hrPermissions, [
            'assign_hr_to_recruitment',
            'make_candidate_decision',
            'schedule_interview',
            'approve_trainee_hr',
        ]);
        $hrRole->syncPermissions($hrPermissions);
        
        // 3. Manager - управление персоналом и утверждение
        $managerRole = Role::where('name', 'manager')->first();
        $managerPermissions = [];
        foreach ($resources as $resource) {
            if (in_array($resource, ['user', 'employment_history', 'position_change_request', 
                'hiring_decision', 'trainee_request', 'assignment', 'shift'])) {
                $managerPermissions = array_merge($managerPermissions, [
                    'view_any_' . $resource,
                    'view_' . $resource,
                    'update_' . $resource,
                ]);
            }
        }
        $managerPermissions = array_merge($managerPermissions, [
            'make_hiring_decision',
            'approve_position_change',
            'approve_trainee_manager',
            'approve_shifts',
            'approve_assignments',
        ]);
        $managerRole->syncPermissions($managerPermissions);
        
        // 4. Dispatcher - диспетчеризация и назначения
        $dispatcherRole = Role::where('name', 'dispatcher')->first();
        $dispatcherPermissions = [];
        foreach ($resources as $resource) {
            if (in_array($resource, ['assignment', 'work_request', 'shift', 'contractor', 
                'contractor_worker', 'mass_personnel_report'])) {
                $dispatcherPermissions = array_merge($dispatcherPermissions, [
                    'view_any_' . $resource,
                    'view_' . $resource,
                    'create_' . $resource,
                    'update_' . $resource,
                ]);
            }
        }
        $dispatcherPermissions = array_merge($dispatcherPermissions, [
            'assign_executors',
            'confirm_assignments',
            'reject_assignments',
            'publish_work_requests',
            'create_work_request_assignment',
            'create_mass_personnel_assignment',
            'edit_assignments',
            'cancel_assignments',
        ]);
        $dispatcherRole->syncPermissions($dispatcherPermissions);
        
        // 5. Initiator - создание запросов
        $initiatorRole = Role::where('name', 'initiator')->first();
        $initiatorPermissions = [
            'view_any_work_request',
            'view_work_request',
            'create_work_request',
            'update_work_request',
            'create_brigadier_schedule',
            'cancel_assignments',
            'publish_work_requests',
        ];
        $initiatorRole->syncPermissions($initiatorPermissions);
        
        $this->command->info('✅ Разрешения назначены ролям HR, Manager, Dispatcher, Initiator');
        
        // ==================== СОЗДАЕМ/ОБНОВЛЯЕМ АДМИНА ====================
        
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Администратор',
                'surname' => 'Системы',
                'patronymic' => '',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+79999999999',
                'user_type' => 'employee',
                'email_verified_at' => now(),
            ]
        );
        
        $admin->assignRole('admin');
        $this->command->info('👤 Администратор создан: admin@example.com / password123');
        
        $this->command->info('🎉 Все разрешения и роли успешно созданы!');
    }
}
