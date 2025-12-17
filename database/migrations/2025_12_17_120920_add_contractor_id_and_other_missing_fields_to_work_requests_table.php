<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Временно отключаем проверку внешних ключей для безопасности
        Schema::disableForeignKeyConstraints();

        Schema::table('work_requests', function (Blueprint $table) {
            // 1. contractor_id - добавляем только если нет
            if (!Schema::hasColumn('work_requests', 'contractor_id')) {
                $table->foreignId('contractor_id')
                    ->nullable()
                    ->after('purpose_id')
                    ->constrained('contractors')
                    ->nullOnDelete()
                    ->comment('Подрядчик (если personnel_type = contractor)');
            }

            // 2. personnel_type - добавляем только если нет
            if (!Schema::hasColumn('work_requests', 'personnel_type')) {
                $table->enum('personnel_type', ['our_staff', 'contractor'])
                    ->nullable()
                    ->after('contractor_id')
                    ->comment('Тип персонала: наши сотрудники или подрядчик');
            }

            // 3. is_custom_address - добавляем только если нет
            if (!Schema::hasColumn('work_requests', 'is_custom_address')) {
                $table->boolean('is_custom_address')
                    ->default(false)
                    ->after('address_id')
                    ->comment('Использовать кастомный адрес вместо справочника');
            }

            // 4. custom_address - добавляем только если нет
            if (!Schema::hasColumn('work_requests', 'custom_address')) {
                $table->text('custom_address')
                    ->nullable()
                    ->after('is_custom_address')
                    ->comment('Кастомный адрес');
            }

            // 5. request_number - добавляем ОСТОРОЖНО с unique
            if (!Schema::hasColumn('work_requests', 'request_number')) {
                // Сначала добавляем как nullable, потом заполним
                $table->string('request_number')
                    ->nullable()
                    ->after('id')
                    ->comment('Внутренний номер заявки (WR-{prefix}-{year}-{sequence})');
            }

            // 6. external_number - добавляем только если нет
            if (!Schema::hasColumn('work_requests', 'external_number')) {
                $table->string('external_number')
                    ->nullable()
                    ->after('request_number')
                    ->comment('Внешний номер заявки от заказчика');
            }

            // 7. desired_workers - добавляем только если нет
            if (!Schema::hasColumn('work_requests', 'desired_workers')) {
                $table->text('desired_workers')
                    ->nullable()
                    ->after('workers_count')
                    ->comment('Желаемые исполнители (имена, контакты)');
            }

            // 8. softDeletes - добавляем только если нет
            if (!Schema::hasColumn('work_requests', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        
        Schema::table('work_requests', function (Blueprint $table) {
            // Удаляем только те колонки, которые мы добавили
            $columnsToDrop = [
                'deleted_at',
                'desired_workers', 
                'external_number',
                'request_number',
                'custom_address',
                'is_custom_address',
                'personnel_type',
                'contractor_id'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('work_requests', $column)) {
                    // Если это внешний ключ, сначала удаляем ограничение
                    if ($column === 'contractor_id') {
                        $table->dropForeign(['contractor_id']);
                    }
                    
                    $table->dropColumn($column);
                }
            }
        });
        
        Schema::enableForeignKeyConstraints();
    }
};
