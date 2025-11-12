<?php
// database/migrations/2025_10_25_xxxxxx_fix_mass_personnel_reports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mass_personnel_reports', function (Blueprint $table) {
            // Переименовываем связь
            if (Schema::hasColumn('mass_personnel_reports', 'request_id')) {
                $table->renameColumn('request_id', 'work_request_id');
            }
            
            // Удаляем foreign keys если они существуют (простой способ)
            $this->safeDropForeign($table, 'mass_personnel_reports', 'brigadier_id');
            $this->safeDropForeign($table, 'mass_personnel_reports', 'contractor_id');
            $this->safeDropForeign($table, 'mass_personnel_reports', 'specialty_id');
            $this->safeDropForeign($table, 'mass_personnel_reports', 'work_type_id');
            
            // Удаляем дублирующие поля
            $columnsToDrop = [
                'brigadier_id', 'contractor_id', 'specialty_id', 
                'work_type_id', 'work_date', 'base_rate', 'expenses_total', 
                'hand_amount', 'payout_amount', 'is_paid', 'status', 'notes'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('mass_personnel_reports', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Добавляем недостающие поля
            if (!Schema::hasColumn('mass_personnel_reports', 'compensation_description')) {
                $table->text('compensation_description')->nullable()->after('compensation_amount');
            }
        });
    }

    public function down()
    {
        Schema::table('mass_personnel_reports', function (Blueprint $table) {
            // Восстанавливаем оригинальное имя связи
            if (Schema::hasColumn('mass_personnel_reports', 'work_request_id')) {
                $table->renameColumn('work_request_id', 'request_id');
            }
            
            // Восстанавливаем удаленные поля
            $table->foreignId('brigadier_id')->nullable()->constrained('users');
            $table->foreignId('contractor_id')->nullable()->constrained();
            $table->foreignId('specialty_id')->nullable()->constrained();
            $table->foreignId('work_type_id')->nullable()->constrained();
            $table->date('work_date')->nullable();
            $table->decimal('base_rate', 10, 2)->default(0);
            $table->decimal('expenses_total', 10, 2)->default(0);
            $table->decimal('hand_amount', 10, 2)->default(0);
            $table->decimal('payout_amount', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            
            // Удаляем добавленное поле
            $table->dropColumn('compensation_description');
        });
    }

    /**
     * Безопасное удаление foreign key
     */
    private function safeDropForeign(Blueprint $table, $tableName, $columnName)
    {
        if (!Schema::hasColumn($tableName, $columnName)) {
            return;
        }
        
        // Пытаемся удалить foreign key
        try {
            $table->dropForeign([$columnName]);
        } catch (\Exception $e) {
            // Игнорируем ошибку если foreign key не существует
        }
    }
};
