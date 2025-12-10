<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $this->info('🔄 Преобразование shift_expenses в универсальные expenses...');

        // 1. Проверяем текущее состояние
        if (Schema::hasTable('shift_expenses') && !Schema::hasTable('expenses')) {
            $this->info('   📊 Найдена таблица shift_expenses, переименовываем...');
            Schema::rename('shift_expenses', 'expenses');
            $this->info('   ✅ Таблица shift_expenses переименована в expenses');
        }

        // 2. Если таблица уже называется expenses, работаем с ней
        if (Schema::hasTable('expenses')) {
            $this->info('   🔍 Анализируем структуру таблицы expenses...');
            
            // Сначала добавляем недостающие поля
            if (!Schema::hasColumn('expenses', 'expensable_id')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->unsignedBigInteger('expensable_id')->nullable()->after('id');
                });
                $this->info('   ✅ Добавлено поле expensable_id');
            }
            
            if (!Schema::hasColumn('expenses', 'expensable_type')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->string('expensable_type')->nullable()->after('expensable_id');
                });
                $this->info('   ✅ Добавлено поле expensable_type');
            }

            // Даем время для применения изменений схемы
            sleep(1);

            // Теперь обновляем данные
            if (Schema::hasColumn('expenses', 'shift_id')) {
                $this->info('   🔄 Переносим данные из shift_id в expensable_id...');
                
                // Сначала копируем shift_id в expensable_id
                DB::statement('
                    UPDATE expenses 
                    SET expensable_id = shift_id 
                    WHERE expensable_id IS NULL 
                    AND shift_id IS NOT NULL
                ');
                
                // Затем устанавливаем expensable_type для этих записей
                DB::statement("
                    UPDATE expenses 
                    SET expensable_type = 'App\\\\Models\\\\Shift' 
                    WHERE expensable_type IS NULL 
                    AND shift_id IS NOT NULL
                ");
                
                $this->info('   ✅ Данные перенесены');
                
                // УДАЛЯЕМ ВНЕШНИЙ КЛЮЧ перед удалением столбца
                $this->info('   🔧 Удаляем внешний ключ expenses_shift_id_foreign...');
                Schema::table('expenses', function (Blueprint $table) {
                    $table->dropForeign(['shift_id']);
                });
                
                // Теперь удаляем shift_id
                Schema::table('expenses', function (Blueprint $table) {
                    $table->dropColumn('shift_id');
                });
                $this->info('   ✅ Поле shift_id удалено');
            }

            // Переименовываем comment в description если нужно
            // Но в таблице у нас description уже есть, а comment нет
            if (Schema::hasColumn('expenses', 'receipt_photo')) {
                $this->info('   📸 Удаляем устаревшее поле receipt_photo...');
                Schema::table('expenses', function (Blueprint $table) {
                    $table->dropColumn('receipt_photo');
                });
                $this->info('   ✅ Поле receipt_photo удалено');
            }

            // Добавляем статус если нужно
            if (!Schema::hasColumn('expenses', 'status')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->string('status')->default('pending')->after('amount');
                });
                $this->info('   ✅ Добавлено поле status');
            }

            // Добавляем name если нужно (из старой структуры)
            if (!Schema::hasColumn('expenses', 'name')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->string('name')->nullable()->after('expensable_type');
                });
                $this->info('   ✅ Добавлено поле name');
            }

            // Обновляем тип поля type если нужно (с enum на string)
            $this->info('   🔄 Обновляем тип поля type...');
            DB::statement("
                ALTER TABLE expenses 
                MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'other'
            ");
            $this->info('   ✅ Поле type изменено на VARCHAR');

            // Проверяем структуру
            $columns = Schema::getColumnListing('expenses');
            $this->info('   📋 Колонки таблицы expenses: ' . implode(', ', $columns));
        }

        // 3. Создаем таблицу contractor_workers если ее нет
        if (!Schema::hasTable('contractor_workers')) {
            Schema::create('contractor_workers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mass_personnel_report_id')->constrained()->onDelete('cascade');
                $table->string('full_name');
                $table->text('notes')->nullable();
                $table->string('photo_missing_reason')->nullable();
                $table->boolean('is_confirmed')->default(false);
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('confirmed_at')->nullable();
                $table->decimal('calculated_hours', 8, 2)->default(0);
                $table->timestamps();
                
                $table->index(['mass_personnel_report_id', 'is_confirmed']);
                $table->index('is_confirmed');
            });
            $this->info('   ✅ Создана таблица contractor_workers');
        }

        // 4. Создаем таблицу mass_personnel_locations если ее нет
        if (!Schema::hasTable('mass_personnel_locations')) {
            Schema::create('mass_personnel_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mass_personnel_report_id')->constrained()->onDelete('cascade');
                $table->string('location');
                $table->integer('personnel_count')->default(0);
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
                
                $table->index('mass_personnel_report_id');
            });
            $this->info('   ✅ Создана таблица mass_personnel_locations');
        }

        // 5. Обновляем массовые отчеты для поддержки новых полей
        if (Schema::hasTable('mass_personnel_reports')) {
            $this->info('   📊 Обновляем таблицу mass_personnel_reports...');
            
            // Сначала переименовываем work_request_id в request_id для единообразия
            if (Schema::hasColumn('mass_personnel_reports', 'work_request_id') && 
                !Schema::hasColumn('mass_personnel_reports', 'request_id')) {
                Schema::table('mass_personnel_reports', function (Blueprint $table) {
                    $table->renameColumn('work_request_id', 'request_id');
                });
                $this->info('   ✅ Переименовано work_request_id → request_id');
            }
            
            $columnsToAdd = [
                ['name' => 'tax_status_id', 'type' => 'unsignedBigInteger', 'nullable' => true],
                ['name' => 'contract_type_id', 'type' => 'unsignedBigInteger', 'nullable' => true],
                ['name' => 'category_id', 'type' => 'unsignedBigInteger', 'nullable' => true],
                ['name' => 'work_type_id', 'type' => 'unsignedBigInteger', 'nullable' => true],
                ['name' => 'base_hourly_rate', 'type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0],
                ['name' => 'total_amount', 'type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0],
                ['name' => 'expenses_total', 'type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0],
                ['name' => 'tax_amount', 'type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0],
                ['name' => 'net_amount', 'type' => 'decimal', 'precision' => 10, 'scale' => 2, 'default' => 0],
                ['name' => 'status', 'type' => 'string', 'default' => 'draft'],
                ['name' => 'submitted_at', 'type' => 'timestamp', 'nullable' => true],
                ['name' => 'approved_at', 'type' => 'timestamp', 'nullable' => true],
                ['name' => 'paid_at', 'type' => 'timestamp', 'nullable' => true],
            ];

            foreach ($columnsToAdd as $column) {
                if (!Schema::hasColumn('mass_personnel_reports', $column['name'])) {
                    Schema::table('mass_personnel_reports', function (Blueprint $table) use ($column) {
                        if ($column['type'] === 'unsignedBigInteger') {
                            $table->unsignedBigInteger($column['name'])->nullable();
                        } elseif ($column['type'] === 'decimal') {
                            $table->decimal($column['name'], $column['precision'], $column['scale'])->default($column['default']);
                        } elseif ($column['type'] === 'string') {
                            $table->string($column['name'])->default($column['default']);
                        } elseif ($column['type'] === 'timestamp') {
                            $table->timestamp($column['name'])->nullable();
                        }
                    });
                    $this->info("   ✅ Добавлено поле {$column['name']} в mass_personnel_reports");
                }
            }
        }

        $this->info('✅ Преобразование таблиц завершено');
    }

    public function down()
    {
        $this->info('⏪ Откат изменений...');

        // 1. Удаляем созданные таблицы
        Schema::dropIfExists('contractor_workers');
        Schema::dropIfExists('mass_personnel_locations');

        // 2. Восстанавливаем expenses в shift_expenses
        if (Schema::hasTable('expenses')) {
            // Восстанавливаем shift_id
            if (!Schema::hasColumn('expenses', 'shift_id')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->unsignedBigInteger('shift_id')->nullable()->after('id');
                });
                
                // Переносим данные обратно
                DB::statement("
                    UPDATE expenses 
                    SET shift_id = expensable_id 
                    WHERE expensable_type = 'App\\\\Models\\\\Shift'
                ");
                
                // Восстанавливаем внешний ключ
                Schema::table('expenses', function (Blueprint $table) {
                    $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
                });
            }

            // Восстанавливаем поле receipt_photo
            if (!Schema::hasColumn('expenses', 'receipt_photo')) {
                Schema::table('expenses', function (Blueprint $table) {
                    $table->string('receipt_photo')->nullable()->after('amount');
                });
            }

            // Удаляем добавленные поля
            $columnsToRemove = ['name', 'status', 'expensable_type', 'expensable_id', 'custom_type'];
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('expenses', $column)) {
                    Schema::table('expenses', function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }

            // Восстанавливаем тип поля type обратно на enum
            DB::statement("
                ALTER TABLE expenses 
                MODIFY COLUMN type ENUM('lunch', 'travel', 'unforeseen') NOT NULL
            ");

            // Переименовываем обратно
            Schema::rename('expenses', 'shift_expenses');
        }

        // 3. Восстанавливаем массовые отчеты
        if (Schema::hasTable('mass_personnel_reports')) {
            // Восстанавливаем work_request_id
            if (Schema::hasColumn('mass_personnel_reports', 'request_id') && 
                !Schema::hasColumn('mass_personnel_reports', 'work_request_id')) {
                Schema::table('mass_personnel_reports', function (Blueprint $table) {
                    $table->renameColumn('request_id', 'work_request_id');
                });
            }
            
            // Удаляем добавленные поля
            $columnsToRemove = [
                'tax_status_id', 'contract_type_id', 'category_id', 'work_type_id',
                'base_hourly_rate', 'total_amount', 'expenses_total', 'tax_amount',
                'net_amount', 'status', 'submitted_at', 'approved_at', 'paid_at'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('mass_personnel_reports', $column)) {
                    Schema::table('mass_personnel_reports', function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        }

        $this->info('✅ Откат завершен');
    }

    private function info($message)
    {
        if (php_sapi_name() === 'cli') {
            echo $message . PHP_EOL;
        }
    }
};
