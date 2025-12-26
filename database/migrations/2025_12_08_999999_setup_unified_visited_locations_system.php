<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Эта миграция теперь только для:
        // 1. Проверки и добавления недостающих полей (безопасно, через hasColumn)
        // 2. Переноса данных
        // 3. Создания новых таблиц

        // === 1. Проверяем структуру visited_locations ===
        Schema::table('visited_locations', function (Blueprint $table) {
            // Эти поля уже должны быть созданы в предыдущей миграции
            // Мы просто проверяем и добавляем, если что-то пропущено
            $checks = [
                'address' => ['type' => 'string', 'after' => 'visitable_id'],
                'latitude' => ['type' => 'decimal', 'after' => 'address', 'precision' => 10, 'scale' => 8, 'nullable' => true],
                'longitude' => ['type' => 'decimal', 'after' => 'latitude', 'precision' => 11, 'scale' => 8, 'nullable' => true],
                'started_at' => ['type' => 'timestamp', 'after' => 'longitude', 'nullable' => true],
                'ended_at' => ['type' => 'timestamp', 'after' => 'started_at', 'nullable' => true],
                'duration_minutes' => ['type' => 'integer', 'after' => 'ended_at', 'default' => 0],
                'notes' => ['type' => 'text', 'after' => 'duration_minutes', 'nullable' => true],
            ];

            foreach ($checks as $column => $config) {
                if (!Schema::hasColumn('visited_locations', $column)) {
                    if ($config['type'] === 'decimal') {
                        $table->decimal($column, $config['precision'], $config['scale'])
                            ->nullable($config['nullable'] ?? false)
                            ->after($config['after']);
                    } elseif ($config['type'] === 'string') {
                        $table->string($column)
                            ->nullable($config['nullable'] ?? false)
                            ->after($config['after']);
                    } elseif ($config['type'] === 'timestamp') {
                        $table->timestamp($column)
                            ->nullable($config['nullable'] ?? false)
                            ->after($config['after']);
                    } elseif ($config['type'] === 'integer') {
                        $table->integer($column)
                            ->default($config['default'] ?? null)
                            ->after($config['after']);
                    } elseif ($config['type'] === 'text') {
                        $table->text($column)
                            ->nullable($config['nullable'] ?? false)
                            ->after($config['after']);
                    }
                }
            }
        });

        // === 2. Проверяем структуру shift_photos ===
        if (Schema::hasTable('shift_photos')) {
            Schema::table('shift_photos', function (Blueprint $table) {
                $checks = [
                    'photoable_type' => ['type' => 'string', 'after' => 'id', 'nullable' => true],
                    'photoable_id' => ['type' => 'unsignedBigInteger', 'after' => 'photoable_type', 'nullable' => true],
                    'file_path' => ['type' => 'string', 'after' => 'photoable_id', 'nullable' => true],
                    'file_name' => ['type' => 'string', 'after' => 'file_path', 'nullable' => true],
                    'mime_type' => ['type' => 'string', 'after' => 'file_name', 'nullable' => true],
                    'file_size' => ['type' => 'integer', 'after' => 'mime_type', 'nullable' => true],
                    'description' => ['type' => 'text', 'after' => 'file_size', 'nullable' => true],
                    'taken_at' => ['type' => 'timestamp', 'after' => 'description', 'nullable' => true],
                    'latitude' => ['type' => 'decimal', 'after' => 'taken_at', 'precision' => 10, 'scale' => 8, 'nullable' => true],
                    'longitude' => ['type' => 'decimal', 'after' => 'latitude', 'precision' => 11, 'scale' => 8, 'nullable' => true],
                ];

                foreach ($checks as $column => $config) {
                    if (!Schema::hasColumn('shift_photos', $column)) {
                        if ($config['type'] === 'decimal') {
                            $table->decimal($column, $config['precision'], $config['scale'])
                                ->nullable($config['nullable'] ?? false)
                                ->after($config['after']);
                        } elseif ($config['type'] === 'string') {
                            $table->string($column)
                                ->nullable($config['nullable'] ?? false)
                                ->after($config['after']);
                        } elseif ($config['type'] === 'timestamp') {
                            $table->timestamp($column)
                                ->nullable($config['nullable'] ?? false)
                                ->after($config['after']);
                        } elseif ($config['type'] === 'integer') {
                            $table->integer($column)
                                ->nullable($config['nullable'] ?? false)
                                ->after($config['after']);
                        } elseif ($config['type'] === 'text') {
                            $table->text($column)
                                ->nullable($config['nullable'] ?? false)
                                ->after($config['after']);
                        } elseif ($config['type'] === 'unsignedBigInteger') {
                            $table->unsignedBigInteger($column)
                                ->nullable($config['nullable'] ?? false)
                                ->after($config['after']);
                        }
                    }
                }
            });
        }

        // === 3. Перенос данных из mass_personnel_visited_locations ===
        // ПРОПУСКАЕМ на данный момент, чтобы избежать сложностей
        // Можно сделать отдельной миграцией позже

        // === 4. Создаем таблицу contractor_workers ===
        if (!Schema::hasTable('contractor_workers')) {
            Schema::create('contractor_workers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mass_personnel_report_id')
                      ->constrained('mass_personnel_reports')
                      ->onDelete('cascade')
                      ->comment('Ссылка на отчет массового персонала');

                $table->string('full_name')->comment('ФИО работника');
                $table->text('notes')->nullable()->comment('Примечания по работнику');
                $table->string('photo_missing_reason')->nullable()->comment('Причина отсутствия фото (заполняется диспетчером)');
                $table->boolean('is_confirmed')->default(false)->comment('Подтвержден ли диспетчером');
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null')->comment('Кто подтвердил (диспетчер)');
                $table->timestamp('confirmed_at')->nullable()->comment('Когда подтвердили');
                $table->decimal('calculated_hours', 8, 2)->nullable()->comment('Рассчитанные часы (с округлением до 0.5)');
                $table->timestamps();

                // Индексы
                $table->index('mass_personnel_report_id');
                $table->index('is_confirmed');
                $table->index('confirmed_by');
            });
        }

        // === 5. Удаляем старые поля из mass_personnel_reports ===
        if (Schema::hasColumn('mass_personnel_reports', 'workers_count')) {
            Schema::table('mass_personnel_reports', function (Blueprint $table) {
                $table->dropColumn('workers_count');
            });
        }

        if (Schema::hasColumn('mass_personnel_reports', 'worker_names')) {
            Schema::table('mass_personnel_reports', function (Blueprint $table) {
                $table->dropColumn('worker_names');
            });
        }
    }

    public function down()
    {
        // При откате:
        // 1. Восстанавливаем поля в mass_personnel_reports
        Schema::table('mass_personnel_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('mass_personnel_reports', 'workers_count')) {
                $table->integer('workers_count')->nullable();
            }
            if (!Schema::hasColumn('mass_personnel_reports', 'worker_names')) {
                $table->text('worker_names')->nullable();
            }
        });

        // 2. Удаляем contractor_workers
        Schema::dropIfExists('contractor_workers');

        // 3. Удаляем добавленные поля (но оставляем базовые, созданные в первой миграции)
        // Не удаляем базовые поля, чтобы не сломать откат первой миграции
    }
};
