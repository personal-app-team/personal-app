<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Department;
use App\Models\EmploymentHistory;
use App\Models\ContractType;
use App\Models\TaxStatus;

class MigrateUserEmploymentData extends Command
{
    protected $signature = 'users:migrate-employment-data';
    protected $description = 'Migrate existing user employment data to new structure';

    public function handle()
    {
        $this->info('Starting employment data migration...');

        // Проверяем, существует ли таблица employment_history
        if (!\Schema::hasTable('employment_history')) {
            $this->error('Table employment_history does not exist. Please run migrations first.');
            return;
        }

        // Создаем дефолтный отдел если нет отделов
        if (Department::count() === 0) {
            $defaultDept = Department::create([
                'name' => 'Основной отдел',
                'description' => 'Автоматически созданный отдел',
                'is_active' => true,
            ]);
            $this->info('Created default department');
        } else {
            $defaultDept = Department::first();
        }

        // Получаем дефолтные contract_type и tax_status если есть
        $defaultContractType = ContractType::first();
        $defaultTaxStatus = TaxStatus::first();

        $users = User::all();
        $migrated = 0;
        $errors = 0;

        foreach ($users as $user) {
            try {
                $this->info("Processing user: {$user->full_name} (ID: {$user->id})");

                // ВРЕМЕННО: Используем прямую логику вместо метода determineUserType
                $userType = $this->determineUserTypeDirectly($user);
                $user->update(['user_type' => $userType]);
                $this->info(" - User type: {$userType}");

                // Для сотрудников создаем запись в employment_history
                if ($userType === 'employee') {
                    $this->createEmploymentHistory($user, $defaultDept, $defaultContractType, $defaultTaxStatus);
                    $migrated++;
                    $this->info(" - Created employment history");
                } else {
                    $this->info(" - Skipped (contractor)");
                }

            } catch (\Exception $e) {
                $this->error("Error migrating user {$user->id}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("Successfully migrated {$migrated} users");
        if ($errors > 0) {
            $this->error("Failed to migrate {$errors} users");
        }
    }

    private function determineUserTypeDirectly(User $user)
    {
        if ($user->hasRole('contractor') || $user->contractor_id) {
            return 'contractor';
        }
        return 'employee';
    }

    private function createEmploymentHistory(User $user, Department $department, $contractType, $taxStatus)
    {
        // НА ПЕРВОМ ЭТАПЕ: Создаем запись без проверки существования
        // Определяем форму занятости на основе ролей
        $employmentForm = $user->hasRole('executor') ? 'temporary' : 'permanent';

        // Определяем тип оплаты
        $paymentType = $user->hasRole('executor') ? 'rate' : 'salary';

        // Создаем базовую запись
        $employmentData = [
            'user_id' => $user->id,
            'employment_form' => $employmentForm,
            'department_id' => $department->id,
            'position' => $this->determinePosition($user),
            'start_date' => $user->created_at ?? now()->subYear(),
            'payment_type' => $paymentType,
            'work_schedule' => '5/2',
            'created_by_id' => 1, // ID администратора
        ];

        // Добавляем contract_type и tax_status если они есть у пользователя
        if ($user->contract_type_id) {
            $employmentData['contract_type_id'] = $user->contract_type_id;
        } elseif ($contractType) {
            $employmentData['contract_type_id'] = $contractType->id;
        }

        if ($user->tax_status_id) {
            $employmentData['tax_status_id'] = $user->tax_status_id;
        } elseif ($taxStatus) {
            $employmentData['tax_status_id'] = $taxStatus->id;
        }

        // Для исполнителей добавляем специальность если есть
        if ($user->hasRole('executor') && $user->specialties()->exists()) {
            $primarySpecialty = $user->specialties()->first();
            $employmentData['primary_specialty_id'] = $primarySpecialty->id;
        }

        EmploymentHistory::create($employmentData);
    }

    private function determinePosition(User $user)
    {
        if ($user->hasRole('admin')) return 'Администратор';
        if ($user->hasRole('initiator')) return 'Инициатор';
        if ($user->hasRole('dispatcher')) return 'Диспетчер';
        if ($user->hasRole('executor')) return 'Исполнитель';
        if ($user->hasRole('hr')) return 'HR специалист';
        if ($user->hasRole('manager')) return 'Менеджер';
        
        return 'Сотрудник';
    }
}
