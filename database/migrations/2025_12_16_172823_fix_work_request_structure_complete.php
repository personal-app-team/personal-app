<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. УДАЛЯЕМ ДУБЛИРУЮЩЕЕ ПОЛЕ mass_personnel_names
        Schema::table('work_requests', function (Blueprint $table) {
            if (Schema::hasColumn('work_requests', 'mass_personnel_names')) {
                $table->dropColumn('mass_personnel_names');
            }
        });

        // 2. ПЕРЕИМЕНОВЫВАЕМ estimated_shift_duration -> estimated_duration_minutes
        Schema::table('work_requests', function (Blueprint $table) {
            if (Schema::hasColumn('work_requests', 'estimated_shift_duration') && 
                !Schema::hasColumn('work_requests', 'estimated_duration_minutes')) {
                $table->renameColumn('estimated_shift_duration', 'estimated_duration_minutes');
            }
        });

        // 3. ДОБАВЛЯЕМ contact_person (его нет в таблице!)
        Schema::table('work_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('work_requests', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('brigadier_id')
                      ->comment('Контактное лицо (если нет бригадира)');
            }
        });

        // 4. Проверяем и добавляем foreign key для contractor_id
        Schema::table('work_requests', function (Blueprint $table) {
            if (Schema::hasColumn('work_requests', 'contractor_id')) {
                // Проверяем существование foreign key через information_schema
                $database = DB::getDatabaseName();
                $foreignKeyExists = DB::table('information_schema.key_column_usage')
                    ->where('constraint_schema', $database)
                    ->where('table_name', 'work_requests')
                    ->where('column_name', 'contractor_id')
                    ->whereNotNull('referenced_table_name')
                    ->exists();
                
                if (!$foreignKeyExists) {
                    $table->foreign('contractor_id')
                          ->references('id')
                          ->on('contractors')
                          ->onDelete('set null');
                }
            }
        });

        // 5. ОБНОВЛЯЕМ ENUM СТАТУСОВ (безопасно)
        try {
            DB::statement("
                ALTER TABLE work_requests 
                MODIFY COLUMN status ENUM(
                    'published',
                    'in_progress', 
                    'closed',
                    'no_shifts',
                    'working',
                    'unclosed',
                    'completed',
                    'cancelled'
                ) DEFAULT 'published'
            ");
        } catch (\Exception $e) {
            // Если ошибка, добавляем значения по одному через ALTER TABLE
            $this->safeAddEnumValues();
        }

        // 6. ДОБАВЛЯЕМ УНИКАЛЬНЫЙ ИНДЕКС ДЛЯ request_number
        Schema::table('work_requests', function (Blueprint $table) {
            // Проверяем, есть ли уже уникальный индекс на request_number
            $indexes = DB::select('SHOW INDEX FROM work_requests WHERE Column_name = ?', ['request_number']);
            
            $hasUniqueIndex = false;
            foreach ($indexes as $index) {
                if ($index->Non_unique == 0) {
                    $hasUniqueIndex = true;
                    break;
                }
            }
            
            if (!$hasUniqueIndex && Schema::hasColumn('work_requests', 'request_number')) {
                $table->unique('request_number');
            }
        });
    }

    /**
     * Безопасное добавление значений ENUM
     */
    private function safeAddEnumValues(): void
    {
        // Список всех нужных статусов
        $allStatuses = [
            'published',
            'in_progress', 
            'closed',
            'no_shifts',
            'working',
            'unclosed',
            'completed',
            'cancelled'
        ];
        
        // Получаем текущие значения из таблицы
        $existingStatuses = DB::table('work_requests')
            ->select(DB::raw('DISTINCT status'))
            ->whereNotNull('status')
            ->pluck('status')
            ->toArray();
        
        // Объединяем
        $enumValues = array_unique(array_merge($existingStatuses, $allStatuses));
        $enumString = "'" . implode("','", $enumValues) . "'";
        
        DB::statement("ALTER TABLE work_requests MODIFY COLUMN status ENUM($enumString) DEFAULT 'published'");
    }

    public function down(): void
    {
        Schema::table('work_requests', function (Blueprint $table) {
            // Восстанавливаем mass_personnel_names
            if (!Schema::hasColumn('work_requests', 'mass_personnel_names')) {
                $table->text('mass_personnel_names')->nullable()->after('contractor_id');
            }
            
            // Восстанавливаем переименование
            if (Schema::hasColumn('work_requests', 'estimated_duration_minutes') && 
                !Schema::hasColumn('work_requests', 'estimated_shift_duration')) {
                $table->renameColumn('estimated_duration_minutes', 'estimated_shift_duration');
            }
            
            // Удаляем contact_person
            if (Schema::hasColumn('work_requests', 'contact_person')) {
                $table->dropColumn('contact_person');
            }
            
            // Удаляем foreign key для contractor_id (если существует)
            if (Schema::hasColumn('work_requests', 'contractor_id')) {
                // Проверяем существование foreign key
                $database = DB::getDatabaseName();
                $foreignKeyExists = DB::table('information_schema.key_column_usage')
                    ->where('constraint_schema', $database)
                    ->where('table_name', 'work_requests')
                    ->where('column_name', 'contractor_id')
                    ->whereNotNull('referenced_table_name')
                    ->exists();
                
                if ($foreignKeyExists) {
                    $table->dropForeign(['contractor_id']);
                }
            }
            
            // Удаляем уникальный индекс request_number
            $indexes = DB::select('SHOW INDEX FROM work_requests WHERE Column_name = ? AND Non_unique = 0', ['request_number']);
            if (!empty($indexes)) {
                $table->dropUnique(['request_number']);
            }
        });
        
        // Восстанавливаем тип статуса
        DB::statement("ALTER TABLE work_requests MODIFY COLUMN status VARCHAR(255) DEFAULT 'draft'");
    }
};
