<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // === 1. Восстановление структуры visited_locations ===
        Schema::table('visited_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('visited_locations', 'address')) {
                $table->string('address')->after('visitable_id');
            }
            
            if (!Schema::hasColumn('visited_locations', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('address');
            }
            
            if (!Schema::hasColumn('visited_locations', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            
            if (!Schema::hasColumn('visited_locations', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('longitude');
            }
            
            if (!Schema::hasColumn('visited_locations', 'ended_at')) {
                $table->timestamp('ended_at')->nullable()->after('started_at');
            }
            
            if (!Schema::hasColumn('visited_locations', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(0)->after('ended_at');
            }
            
            if (!Schema::hasColumn('visited_locations', 'notes')) {
                $table->text('notes')->nullable()->after('duration_minutes');
            }
        });

        // Добавляем индекс для visited_locations только если его нет
        $this->addIndexIfNotExists(
            'visited_locations',
            'visited_locations_visitable_type_visitable_id_index',
            ['visitable_type', 'visitable_id']
        );

        // === 2. Восстановление структуры shift_photos ===
        Schema::table('shift_photos', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_photos', 'photoable_type')) {
                $table->string('photoable_type')->nullable()->after('id');
            }
            
            if (!Schema::hasColumn('shift_photos', 'photoable_id')) {
                $table->unsignedBigInteger('photoable_id')->nullable()->after('photoable_type');
            }
            
            if (!Schema::hasColumn('shift_photos', 'file_path')) {
                $table->string('file_path')->nullable()->after('photoable_id');
            }
            
            if (!Schema::hasColumn('shift_photos', 'file_name')) {
                $table->string('file_name')->nullable()->after('file_path');
            }
            
            if (!Schema::hasColumn('shift_photos', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('file_name');
            }
            
            if (!Schema::hasColumn('shift_photos', 'file_size')) {
                $table->integer('file_size')->nullable()->after('mime_type');
            }
            
            if (!Schema::hasColumn('shift_photos', 'description')) {
                $table->text('description')->nullable()->after('file_size');
            }
            
            if (!Schema::hasColumn('shift_photos', 'taken_at')) {
                $table->timestamp('taken_at')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('shift_photos', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('taken_at');
            }
            
            if (!Schema::hasColumn('shift_photos', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });

        // Добавляем индекс для shift_photos только если его нет
        $this->addIndexIfNotExists(
            'shift_photos',
            'shift_photos_photoable_type_photoable_id_index',
            ['photoable_type', 'photoable_id']
        );

        // === 3. Перенос данных из mass_personnel_visited_locations ===
        if (Schema::hasTable('mass_personnel_visited_locations')) {
            $hasData = DB::table('mass_personnel_visited_locations')->exists();
            
            if ($hasData) {
                DB::table('mass_personnel_visited_locations')->orderBy('id')->chunk(100, function ($locations) {
                    foreach ($locations as $location) {
                        // Определяем address
                        $address = '';
                        if ($location->address_id) {
                            $addressRecord = DB::table('addresses')->find($location->address_id);
                            $address = $addressRecord ? ($addressRecord->address ?? '') : '';
                        } elseif ($location->custom_address) {
                            $address = $location->custom_address;
                        }
                        
                        // Вставляем в visited_locations
                        DB::table('visited_locations')->insert([
                            'visitable_type' => 'App\\Models\\MassPersonnelReport',
                            'visitable_id' => $location->mass_personnel_report_id,
                            'address' => $address,
                            'latitude' => $location->latitude ?? null,
                            'longitude' => $location->longitude ?? null,
                            'started_at' => $location->started_at,
                            'ended_at' => $location->ended_at,
                            'duration_minutes' => $location->duration_minutes,
                            'notes' => $location->notes,
                            'created_at' => $location->created_at,
                            'updated_at' => $location->updated_at,
                        ]);
                        
                        // Если есть фото
                        if ($location->photo_path) {
                            $visitedLocationId = DB::getPdo()->lastInsertId();
                            
                            DB::table('shift_photos')->insert([
                                'photoable_type' => 'App\\Models\\VisitedLocation',
                                'photoable_id' => $visitedLocationId,
                                'file_path' => $location->photo_path,
                                'file_name' => basename($location->photo_path),
                                'mime_type' => $this->getMimeType($location->photo_path),
                                'file_size' => 0,
                                'description' => 'Фото локации из старой системы',
                                'taken_at' => $location->ended_at ?? $location->started_at,
                                'created_at' => $location->created_at,
                                'updated_at' => $location->updated_at,
                            ]);
                        }
                    }
                });
            }
            
            // Удаляем старую таблицу
            Schema::dropIfExists('mass_personnel_visited_locations');
        }

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
    
    /**
     * Добавляет индекс, если он еще не существует
     */
    private function addIndexIfNotExists(string $tableName, string $indexName, array $columns): void
    {
        $indexExists = false;
        try {
            $result = DB::selectOne("
                SELECT COUNT(*) as count 
                FROM information_schema.statistics 
                WHERE table_schema = DATABASE() 
                AND table_name = ? 
                AND index_name = ?
            ", [$tableName, $indexName]);
            
            $indexExists = $result && $result->count > 0;
        } catch (\Exception $e) {
            // Если ошибка запроса, считаем что индекса нет
            $indexExists = false;
        }
        
        if (!$indexExists) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                $table->index($columns);
            });
        }
    }
    
    private function getMimeType($path)
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        return match($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    public function down()
    {
        // При откате восстанавливаем mass_personnel_visited_locations
        if (!Schema::hasTable('mass_personnel_visited_locations')) {
            Schema::create('mass_personnel_visited_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mass_personnel_report_id')->constrained()->onDelete('cascade');
                $table->foreignId('address_id')->nullable()->constrained('addresses')->onDelete('set null');
                $table->string('custom_address')->nullable();
                $table->timestamp('started_at');
                $table->timestamp('ended_at')->nullable();
                $table->integer('duration_minutes')->default(0);
                $table->string('photo_path')->nullable();
                $table->boolean('is_last_location')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
        
        // Удаляем contractor_workers
        Schema::dropIfExists('contractor_workers');
        
        // Восстанавливаем поля в mass_personnel_reports
        Schema::table('mass_personnel_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('mass_personnel_reports', 'workers_count')) {
                $table->integer('workers_count')->nullable();
            }
            if (!Schema::hasColumn('mass_personnel_reports', 'worker_names')) {
                $table->text('worker_names')->nullable();
            }
        });
        
        // Удаляем индексы
        Schema::table('visited_locations', function (Blueprint $table) {
            $table->dropIndex(['visitable_type', 'visitable_id']);
        });
        
        Schema::table('shift_photos', function (Blueprint $table) {
            $table->dropIndex(['photoable_type', 'photoable_id']);
        });
    }
};
