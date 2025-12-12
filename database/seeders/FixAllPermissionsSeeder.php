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
        
        // НЕ УДАЛЯЕМ существующие разрешения и роли!
        // Вместо этого используем firstOrCreate для всех
        
        $this->command->info('🔄 Создание отсутствующих разрешений для Filament...');
        
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
        
        $this->command->info('✅ Созданы разрешения для ресурсов');
        
        // 2. Специальные разрешения (создаем только если нет)
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
            'assign_executors',
            
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
        
        $this->command->info('✅ Созданы специальные разрешения');
        
        // 3. Создаем только БАЗОВЫЕ роли (если их нет)
        $basicRoles = [
            'admin' => 'Администратор (полный доступ)',
            'hr' => 'HR-специалист',
            'manager' => 'Руководитель',
            'dispatcher' => 'Диспетчер',
            'initiator' => 'Инициатор',
            'executor' => 'Исполнитель',
            'trainee' => 'Стажер',
            'viewer' => 'Наблюдатель',
            // Роли подрядчиков создаются через миграцию, НЕ создаем здесь
        ];
        
        foreach ($basicRoles as $name => $description) {
            Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web'
            ]);
        }
        
        $this->command->info('✅ Базовые роли созданы');
        
        // 4. Админ получает ВСЕ разрешения
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions(Permission::all());
            $this->command->info('👑 Админу назначены все разрешения');
        }
        
        $this->command->info('🎉 Разрешения и роли обновлены!');
    }
}
