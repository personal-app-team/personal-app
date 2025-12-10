<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\ContractType;
use App\Models\TaxStatus;
use App\Models\EmploymentHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Начинаем создание супер-администратора системы...');
        
        // ШАГ 1: Создаем базовые справочники в правильном порядке
        $this->createBasicReferences();
        
        // ШАГ 2: Создаем разрешения (только необходимые поля)
        $this->createPermissions();
        
        // ШАГ 3: Создаем роль супер-админа (без description)
        $superAdminRole = $this->createSuperAdminRole();
        
        // ШАГ 4: Создаем пользователя (БЕЗ поля full_name)
        $adminUser = $this->createAdminUser();
        
        // ШАГ 5: Назначаем роль
        $adminUser->assignRole($superAdminRole);
        
        // ШАГ 6: Создаем историю трудоустройства
        $this->createEmploymentHistory($adminUser);
        
        $this->command->info('🎉 СУПЕР-АДМИНИСТРАТОР СОЗДАН!');
        $this->command->info('📧 Email: admin@example.com');
        $this->command->info('🔑 Пароль: password123');
        $this->command->info('👔 Отдел: IT');
        $this->command->info('👑 Роль: super-admin');
    }
    
    private function createBasicReferences(): void
    {
        $this->command->info('📋 Создаем базовые справочники в правильном порядке...');
        
        // 1. Сначала создаем ContractType (нужен для TaxStatus)
        $this->command->info('  1. Создаем ContractType...');
        $contractType = ContractType::firstOrCreate(
            ['name' => 'Трудовой договор'],
            [
                'code' => 'TD',
                'description' => 'Основной трудовой договор по ТК РФ',
                'is_active' => 1,
            ]
        );
        
        // 2. Создаем TaxStatus (требует contract_type_id и tax_rate)
        $this->command->info('  2. Создаем TaxStatus...');
        $taxStatus = TaxStatus::firstOrCreate(
            [
                'name' => 'Резидент РФ',
                'contract_type_id' => $contractType->id,
            ],
            [
                'tax_rate' => 13.000, // Ставка НДФЛ 13%
                'description' => 'Налоговый резидент Российской Федерации',
                'is_active' => 1,
                'is_default' => 1,
            ]
        );
        
        // 3. Создаем Department
        $this->command->info('  3. Создаем Department...');
        $department = Department::firstOrCreate(
            ['name' => 'IT'],
            [
                'description' => 'Отдел информационных технологий',
                'parent_id' => null,
                'manager_id' => null, // Будет назначен позже
                'is_active' => 1,
            ]
        );
        
        $this->command->info('✅ Все базовые справочники созданы');
    }
    
    private function createPermissions(): void
    {
        $this->command->info('🔐 Создаем системные разрешения...');
        
        // Базовые разрешения для Filament (только существующие поля)
        $resources = [
            'user', 'role', 'permission', 'assignment', 'shift', 'work_request',
            'candidate', 'vacancy', 'recruitment_request', 'interview', 
            'hiring_decision', 'department', 'employment_history',
            'contractor', 'category', 'specialty', 'activity_log',
        ];
        
        $actions = ['view_any', 'view', 'create', 'update', 'delete'];
        
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action}_{$resource}",
                    'guard_name' => 'web',
                ]);
            }
        }
        
        // Специальные разрешения (только существующие поля)
        Permission::firstOrCreate([
            'name' => 'access_filament',
            'guard_name' => 'web',
        ]);
        
        Permission::firstOrCreate([
            'name' => 'view_reports',
            'guard_name' => 'web',
        ]);
        
        $this->command->info('✅ Разрешения созданы: ' . Permission::count() . ' шт.');
    }
    
    private function createSuperAdminRole(): Role
    {
        $this->command->info('👑 Создаем роль супер-администратора...');
        
        // Только существующие поля: name и guard_name
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
        
        // Назначаем ВСЕ разрешения
        $superAdminRole->syncPermissions(Permission::all());
        
        $this->command->info('✅ Роль super-admin создана с ' . $superAdminRole->permissions->count() . ' разрешениями');
        
        return $superAdminRole;
    }
    
    private function createAdminUser(): User
    {
        $this->command->info('👤 Создаем пользователя администратора...');
        
        $adminUser = User::where('email', 'admin@example.com')->first();
        
        if (!$adminUser) {
            // Создаем нового пользователя БЕЗ поля full_name
            $adminUser = User::create([
                'name' => 'Администратор',
                'surname' => 'Системы',
                'patronymic' => '', // Добавляем для корректного вычисления full_name
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+79999999999',
                'user_type' => 'employee',
                'email_verified_at' => now(),
            ]);
            $this->command->info('✅ Пользователь создан');
        } else {
            // Обновляем существующего БЕЗ поля full_name
            $adminUser->update([
                'password' => Hash::make('password123'),
                'name' => 'Администратор',
                'surname' => 'Системы',
                'patronymic' => '',
                'user_type' => 'employee',
            ]);
            $this->command->info('⚠️ Пользователь уже существовал, обновлен');
        }
        
        return $adminUser;
    }
    
    private function createEmploymentHistory(User $user): void
    {
        $this->command->info('📝 Создаем историю трудоустройства...');
        
        // Находим справочники
        $itDepartment = Department::where('name', 'IT')->first();
        $contractType = ContractType::where('name', 'Трудовой договор')->first();
        $taxStatus = TaxStatus::where('name', 'Резидент РФ')->first();
        
        if (!$itDepartment || !$contractType || !$taxStatus) {
            $this->command->error('❌ Не удалось найти справочники для истории трудоустройства');
            return;
        }
        
        // Создаем или обновляем историю трудоустройства
        EmploymentHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'end_date' => null, // Текущая должность
            ],
            [
                'department_id' => $itDepartment->id,
                'position' => 'Главный администратор системы',
                'employment_form' => 'permanent', // Используем допустимое значение из enum
                'contract_type_id' => $contractType->id,
                'tax_status_id' => $taxStatus->id,
                'payment_type' => 'salary', // Используем допустимое значение из enum
                'salary_amount' => 0,
                'has_overtime' => 0,
                'work_schedule' => '5/2', // Используем допустимое значение из enum
                'start_date' => now()->subYear(),
                'notes' => 'Супер-администратор системы. Создан автоматически.',
                'created_by_id' => $user->id, // Обязательное поле
            ]
        );
        
        $this->command->info('✅ История трудоустройства создана');
    }
}
