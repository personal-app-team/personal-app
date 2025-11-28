<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\EmploymentHistory;

return new class extends Migration
{
    public function up()
    {
        // 1. Добавляем временные поля в employment_history если их еще нет
        Schema::table('employment_history', function (Blueprint $table) {
            if (!Schema::hasColumn('employment_history', 'contract_type_id')) {
                $table->foreignId('contract_type_id')->nullable()->constrained()->after('termination_date');
            }
            if (!Schema::hasColumn('employment_history', 'tax_status_id')) {
                $table->foreignId('tax_status_id')->nullable()->constrained()->after('contract_type_id');
            }
        });

        // 2. Переносим данные из users в employment_history
        $users = User::whereNotNull('contract_type_id')->orWhereNotNull('tax_status_id')->get();
        
        foreach ($users as $user) {
            // Находим или создаем запись employment_history для пользователя
            $employment = EmploymentHistory::where('user_id', $user->id)->first();
            
            if (!$employment) {
                // Если записи нет, создаем базовую
                $employment = EmploymentHistory::create([
                    'user_id' => $user->id,
                    'employment_form' => 'permanent', // по умолчанию
                    'department_id' => \App\Models\Department::first()->id ?? 1,
                    'position' => $this->determinePosition($user),
                    'start_date' => now()->subYear(),
                    'payment_type' => 'rate',
                    'work_schedule' => '5/2',
                    'created_by_id' => 1,
                ]);
            }
            
            // Обновляем поля contract_type_id и tax_status_id
            $employment->update([
                'contract_type_id' => $user->contract_type_id,
                'tax_status_id' => $user->tax_status_id,
            ]);
        }

        // 3. Удаляем поля из users (позже, после проверки)
        // Пока НЕ удаляем - сделаем это отдельной миграцией после тестирования
    }

    public function down()
    {
        // При откате - не восстанавливаем поля в users, т.к. данные уже в employment_history
        Schema::table('employment_history', function (Blueprint $table) {
            $table->dropForeign(['contract_type_id']);
            $table->dropForeign(['tax_status_id']);
            $table->dropColumn(['contract_type_id', 'tax_status_id']);
        });
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
};
