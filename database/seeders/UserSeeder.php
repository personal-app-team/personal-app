<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Contractor;
use App\Models\ContractType;
use App\Models\TaxStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('👥 Создание тестовых пользователей...');
        
        // Получаем типы договоров и налоговые статусы
        $contractType = ContractType::first();
        $taxStatus = TaxStatus::first();
        
        if (!$contractType || !$taxStatus) {
            $this->command->error('❌ Сначала запустите сидер ContractTypeTaxStatusSeeder!');
            return;
        }

        // Создаем компании-подрядчики с НОВОЙ структурой
        $this->command->info('🏢 Создание компаний-подрядчиков...');

        // Создаем подрядчиков с правильными кодами
        $contractor1 = Contractor::create([
            'name' => 'ООО "СтройМонтаж"',
            'contractor_code' => 'SMT', // Явно задаем осмысленный код
            'inn' => '7701234567',
            'address' => 'г. Москва, ул. Строителей, д. 1',
            'bank_details' => 'Банк ВТБ, р/с 40702810123456789001, к/с 30101810700000000187, БИК 044525187',
            'director' => 'Петров Алексей Сергеевич',
            'director_phone' => '+79991234561',
            'director_email' => 'petrov@stroymontag.ru',
            'company_phone' => '+74951234561',
            'company_email' => 'info@stroymontag.ru',
            'contract_type_id' => $contractType->id,
            'tax_status_id' => $taxStatus->id,
            'is_active' => true,
            'notes' => 'Подрядчик на строительно-монтажные работы',
        ]);

        $contractor2 = Contractor::create([
            'name' => 'ООО "ЭлектроСервис"',
            'contractor_code' => 'ELS', // Явно задаем код
            'inn' => '7701234568',
            'address' => 'г. Москва, ул. Электриков, д. 2',
            'bank_details' => 'Банк ВТБ, р/с 40702810123456789002, к/с 30101810700000000187, БИК 044525187',
            'director' => 'Иванова Мария Владимировна',
            'director_phone' => '+79991234562',
            'director_email' => 'ivanova@electroservice.ru',
            'company_phone' => '+74951234562',
            'company_email' => 'info@electroservice.ru',
            'contract_type_id' => $contractType->id,
            'tax_status_id' => $taxStatus->id,
            'is_active' => true,
            'notes' => 'Подрядчик на электромонтажные работы',
        ]);

        $contractor3 = Contractor::create([
            'name' => 'ООО "КлинингПро"',
            'contractor_code' => 'CLP', // Явно задаем код
            'inn' => '7701234569',
            'address' => 'г. Москва, ул. Чистая, д. 3',
            'bank_details' => 'Банк ВТБ, р/с 40702810123456789003, к/с 30101810700000000187, БИК 044525187',
            'director' => 'Сидорова Ольга Петровна',
            'director_phone' => '+79991234563',
            'director_email' => 'sidorova@cleaningpro.ru',
            'company_phone' => '+74951234563',
            'company_email' => 'info@cleaningpro.ru',
            'contract_type_id' => $contractType->id,
            'tax_status_id' => $taxStatus->id,
            'is_active' => true,
            'notes' => 'Подрядчик на клининговые услуги',
        ]);

        $contractor4 = Contractor::create([
            'name' => 'ООО "ЛандшафтныйДизайн"',
            'contractor_code' => 'LDS', // Явно задаем код
            'inn' => '7701234570',
            'address' => 'г. Москва, ул. Зеленая, д. 4',
            'bank_details' => 'Банк ВТБ, р/с 40702810123456789004, к/с 30101810700000000187, БИК 044525187',
            'director' => 'Козлов Иван Михайлович',
            'director_phone' => '+79991234564',
            'director_email' => 'kozlov@landdesign.ru',
            'company_phone' => '+74951234564',
            'company_email' => 'info@landdesign.ru',
            'contract_type_id' => $contractType->id,
            'tax_status_id' => $taxStatus->id,
            'is_active' => true,
            'notes' => 'Подрядчик на ландшафтные работы',
        ]);

        $this->command->info('✅ Подрядчики созданы с новой структурой');
        
        // ... остальная часть сидера без изменений
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
            'contractor_id' => $contractor1->id, // Связываем с подрядчиком
            'email_verified_at' => now(),
        ]);
        $contractorAdmin->assignRole('contractor_admin');
        
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
        
        $this->command->info('🎉 Все тестовые пользователи созданы!');
        $this->command->info('📊 Статистика:');
        $this->command->info('  • Подрядчики: 4');
        $this->command->info('  • Инициаторы: 3');
        $this->command->info('  • Диспетчеры: 2');
        $this->command->info('  • Наши исполнители: 10');
        $this->command->info('  • Админ подрядчика: 1');
        $this->command->info('  • Диспетчеры подрядчика: 2');
        $this->command->info('  • Исполнители подрядчика: 5');
        $this->command->info('  • HR: 3');
        $this->command->info('  • Менеджеры: 3');
        $this->command->info('  • Стажеры: 2');
    }
}
