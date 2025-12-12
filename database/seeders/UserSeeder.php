<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Contractor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('👥 Создание тестовых пользователей...');
        
        // Создаем компании-подрядчики
        $this->command->info('🏢 Создание компаний-подрядчиков...');
        
        $contractor1 = Contractor::create([
            'name' => 'ООО "СтройМонтаж"',
            'contact_person' => 'Петров Алексей Сергеевич',
            'phone' => '+79991234561',
            'email' => 'info@stroymontag.ru',
            'is_active' => true,
            'specializations' => [],
        ]);
        
        $contractor2 = Contractor::create([
            'name' => 'ИП "ЭлектроСервис"',
            'contact_person' => 'Иванова Мария Владимировна',
            'phone' => '+79991234562',
            'email' => 'info@electroservice.ru',
            'is_active' => true,
            'specializations' => [],
        ]);
        
        // 1. Инициаторы (3 пользователя)
        $this->command->info('📋 Создание инициаторов...');
        for ($i = 1; $i <= 3; $i++) {
            $user = User::create([
                'name' => 'Инициатор',
                'surname' => 'Инициаторов' . $i,
                'patronymic' => 'Инициаторович',
                'email' => 'initiator' . $i . '@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+7999111000' . $i,
                'user_type' => 'employee',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('initiator');
        }
        
        // 2. Диспетчеры (2 пользователя)
        $this->command->info('📞 Создание диспетчеров...');
        for ($i = 1; $i <= 2; $i++) {
            $user = User::create([
                'name' => 'Диспетчер',
                'surname' => 'Диспетчеров' . $i,
                'patronymic' => 'Диспетчерович',
                'email' => 'dispatcher' . $i . '@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+7999112000' . $i,
                'user_type' => 'employee',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('dispatcher');
        }
        
        // 3. Наши исполнители (10 пользователей)
        $this->command->info('👷 Создание наших исполнителей...');
        for ($i = 1; $i <= 10; $i++) {
            $user = User::create([
                'name' => 'Исполнитель',
                'surname' => 'Исполнителев' . $i,
                'patronymic' => 'Исполнителевич',
                'email' => 'executor' . $i . '@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+7999113000' . $i,
                'user_type' => 'employee',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('executor');
        }
        
        // 4. Админ подрядчика (1 пользователь - управляющий компанией)
        $this->command->info('👑 Создание администратора подрядчика...');
        $contractorAdmin = User::create([
            'name' => 'Алексей',
            'surname' => 'Петров',
            'patronymic' => 'Сергеевич',
            'email' => 'admin@stroymontag.ru',
            'password' => Hash::make('password123'),
            'phone' => '+7999114001',
            'user_type' => 'contractor',
            'email_verified_at' => now(),
        ]);
        $contractorAdmin->assignRole('contractor_admin');
        // Связываем пользователя с компанией (как управляющего)
        $contractor1->user_id = $contractorAdmin->id;
        $contractor1->save();
        
        // 5. Диспетчеры подрядчика (2 пользователя - привязаны к компании)
        $this->command->info('📞 Создание диспетчеров подрядчика...');
        for ($i = 1; $i <= 2; $i++) {
            $user = User::create([
                'name' => 'Диспетчер',
                'surname' => 'Подрядчиков' . $i,
                'patronymic' => 'Подрядчикович',
                'email' => 'dispatcher' . $i . '@stroymontag.ru',
                'password' => Hash::make('password123'),
                'phone' => '+799911400' . ($i + 1),
                'contractor_id' => $contractor1->id,
                'user_type' => 'contractor',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('contractor_dispatcher');
        }
        
        // 6. Исполнители подрядчика (5 пользователей - привязаны к компании)
        $this->command->info('🏢 Создание исполнителей подрядчика...');
        for ($i = 1; $i <= 5; $i++) {
            $user = User::create([
                'name' => 'Исполнитель',
                'surname' => 'Подрядный' . $i,
                'patronymic' => 'Подряднович',
                'email' => 'executor' . $i . '@stroymontag.ru',
                'password' => Hash::make('password123'),
                'phone' => '+799911400' . ($i + 3),
                'contractor_id' => $contractor1->id,
                'user_type' => 'contractor',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('contractor_executor');
        }
        
        // 7. HR-специалисты (3 пользователя)
        $this->command->info('📋 Создание HR-специалистов...');
        for ($i = 1; $i <= 3; $i++) {
            $user = User::create([
                'name' => 'HR',
                'surname' => 'HR-специалист' . $i,
                'patronymic' => 'HR-ович',
                'email' => 'hr' . $i . '@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+7999115000' . $i,
                'user_type' => 'employee',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('hr');
        }
        
        // 8. Менеджеры (3 пользователя)
        $this->command->info('👔 Создание менеджеров...');
        for ($i = 1; $i <= 3; $i++) {
            $user = User::create([
                'name' => 'Менеджер',
                'surname' => 'Менеджеров' . $i,
                'patronymic' => 'Менеджерович',
                'email' => 'manager' . $i . '@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+7999116000' . $i,
                'user_type' => 'employee',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('manager');
        }
        
        // 9. Стажеры (2 пользователя)
        $this->command->info('🎓 Создание стажеров...');
        for ($i = 1; $i <= 2; $i++) {
            $user = User::create([
                'name' => 'Стажер',
                'surname' => 'Стажеров' . $i,
                'patronymic' => 'Стажерович',
                'email' => 'trainee' . $i . '@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+7999117000' . $i,
                'user_type' => 'employee',
                'email_verified_at' => now(),
            ]);
            $user->assignRole('trainee');
        }
        
        // 10. Наблюдатели (1 пользователь)
        $this->command->info('👁️ Создание наблюдателя...');
        $viewer = User::create([
            'name' => 'Наблюдатель',
            'surname' => 'Наблюдателев',
            'patronymic' => 'Наблюдателевич',
            'email' => 'viewer@example.com',
            'password' => Hash::make('password123'),
            'phone' => '+7999118001',
            'user_type' => 'employee',
            'email_verified_at' => now(),
        ]);
        $viewer->assignRole('viewer');
        
        $this->command->info('🎉 Все тестовые пользователи созданы!');
        $this->command->info('📊 Статистика:');
        $this->command->info('  • Инициаторы: 3');
        $this->command->info('  • Диспетчеры: 2');
        $this->command->info('  • Наши исполнители: 10');
        $this->command->info('  • Админ подрядчика: 1');
        $this->command->info('  • Диспетчеры подрядчика: 2');
        $this->command->info('  • Исполнители подрядчика: 5');
        $this->command->info('  • HR: 3');
        $this->command->info('  • Менеджеры: 3');
        $this->command->info('  • Стажеры: 2');
        $this->command->info('  • Наблюдатель: 1');
    }
}
