<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Если таблица shift_photos существует, переименовываем ее в photos
        if (Schema::hasTable('shift_photos')) {
            echo "🔄 Переименовываем shift_photos в photos\n";
            
            // Если photos уже существует, удаляем ее (только если пустая)
            if (Schema::hasTable('photos')) {
                $photoCount = DB::table('photos')->count();
                if ($photoCount === 0) {
                    Schema::dropIfExists('photos');
                    echo "🗑️ Удалена пустая таблица photos\n";
                } else {
                    echo "⚠️ Таблица photos не пустая, оставляем как есть\n";
                }
            }
            
            Schema::rename('shift_photos', 'photos');
            echo "✅ Таблица shift_photos переименована в photos\n";
        }
        
        // 2. Теперь работаем с таблицей photos (должна существовать)
        if (!Schema::hasTable('photos')) {
            echo "📝 Создаем новую таблицу photos\n";
            Schema::create('photos', function (Blueprint $table) {
                $table->id();
                $table->string('photoable_type');
                $table->unsignedBigInteger('photoable_id');
                $table->string('file_path');
                $table->string('file_name');
                $table->string('original_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedInteger('file_size')->nullable();
                $table->text('description')->nullable();
                $table->timestamp('taken_at')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('photo_type')->default('other');
                $table->boolean('is_verified')->default(false);
                $table->unsignedBigInteger('verified_by_id')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                // Индексы
                $table->index(['photoable_type', 'photoable_id']);
                $table->index('photo_type');
                $table->index('is_verified');
                $table->index('verified_by_id');

                // Внешний ключ
                $table->foreign('verified_by_id')
                      ->references('id')
                      ->on('users')
                      ->nullOnDelete();
            });
            echo "✅ Таблица photos создана с полной структурой\n";
        } else {
            echo "📋 Таблица photos уже существует, добавляем недостающие поля\n";
            
            // Добавляем недостающие колонки в существующую таблицу
            Schema::table('photos', function (Blueprint $table) {
                // Проверяем и добавляем каждое поле, если его нет
                $columnsToAdd = [
                    'photoable_type' => function (Blueprint $table) {
                        $table->string('photoable_type')->nullable()->after('id');
                    },
                    'photoable_id' => function (Blueprint $table) {
                        $table->unsignedBigInteger('photoable_id')->nullable()->after('photoable_type');
                    },
                    'file_path' => function (Blueprint $table) {
                        $table->string('file_path')->nullable()->after('photoable_id');
                    },
                    'file_name' => function (Blueprint $table) {
                        $table->string('file_name')->nullable()->after('file_path');
                    },
                    'original_name' => function (Blueprint $table) {
                        $table->string('original_name')->nullable()->after('file_name');
                    },
                    'mime_type' => function (Blueprint $table) {
                        $table->string('mime_type')->nullable()->after('original_name');
                    },
                    'file_size' => function (Blueprint $table) {
                        $table->unsignedInteger('file_size')->nullable()->after('mime_type');
                    },
                    'description' => function (Blueprint $table) {
                        $table->text('description')->nullable()->after('file_size');
                    },
                    'taken_at' => function (Blueprint $table) {
                        $table->timestamp('taken_at')->nullable()->after('description');
                    },
                    'latitude' => function (Blueprint $table) {
                        $table->decimal('latitude', 10, 7)->nullable()->after('taken_at');
                    },
                    'longitude' => function (Blueprint $table) {
                        $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                    },
                    'photo_type' => function (Blueprint $table) {
                        $table->string('photo_type')->default('other')->after('longitude');
                    },
                    'is_verified' => function (Blueprint $table) {
                        $table->boolean('is_verified')->default(false)->after('photo_type');
                    },
                    'verified_by_id' => function (Blueprint $table) {
                        $table->unsignedBigInteger('verified_by_id')->nullable()->after('is_verified');
                    },
                    'verified_at' => function (Blueprint $table) {
                        $table->timestamp('verified_at')->nullable()->after('verified_by_id');
                    },
                ];
                
                foreach ($columnsToAdd as $column => $callback) {
                    if (!Schema::hasColumn('photos', $column)) {
                        $callback($table);
                        echo "✅ Добавлено поле: {$column}\n";
                    }
                }
                
                // Добавляем индексы
                if (!$this->indexExists('photos', ['photoable_type', 'photoable_id'])) {
                    $table->index(['photoable_type', 'photoable_id']);
                }
                
                if (!$this->indexExists('photos', ['photo_type'])) {
                    $table->index(['photo_type']);
                }
                
                if (!$this->indexExists('photos', ['is_verified'])) {
                    $table->index(['is_verified']);
                }
                
                if (!$this->indexExists('photos', ['verified_by_id'])) {
                    $table->index(['verified_by_id']);
                }
                
                // Внешний ключ
                if (Schema::hasColumn('photos', 'verified_by_id')) {
                    $foreignKeyExists = DB::selectOne("
                        SELECT COUNT(*) as count 
                        FROM information_schema.TABLE_CONSTRAINTS 
                        WHERE CONSTRAINT_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'photos' 
                        AND CONSTRAINT_NAME = 'photos_verified_by_id_foreign'
                    ");
                    
                    if (!$foreignKeyExists || $foreignKeyExists->count == 0) {
                        $table->foreign('verified_by_id')
                              ->references('id')
                              ->on('users')
                              ->nullOnDelete();
                    }
                }
            });
        }

        // 3. Обновляем типы существующих фотографий (если есть данные)
        $this->updateExistingPhotoTypes();
    }

    public function down(): void
    {
        // При откате оставляем таблицу photos, но удаляем добавленные поля
        Schema::table('photos', function (Blueprint $table) {
            $columnsToDrop = [
                'verified_at',
                'verified_by_id',
                'is_verified',
                'photo_type',
                'longitude',
                'latitude',
                'taken_at',
                'description',
                'file_size',
                'mime_type',
                'original_name',
                'file_name',
                'file_path',
                'photoable_id',
                'photoable_type'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('photos', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Удаляем индексы
            $table->dropIndexIfExists('photos_photoable_type_photoable_id_index');
            $table->dropIndexIfExists('photos_photo_type_index');
            $table->dropIndexIfExists('photos_is_verified_index');
            $table->dropIndexIfExists('photos_verified_by_id_index');
            
            // Удаляем внешний ключ
            $foreignKeyExists = DB::selectOne("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'photos' 
                AND CONSTRAINT_NAME = 'photos_verified_by_id_foreign'
            ");
            
            if ($foreignKeyExists && $foreignKeyExists->count > 0) {
                $table->dropForeign(['verified_by_id']);
            }
        });
        
        // Не переименовываем обратно, так как исходная таблица shift_photos могла не существовать
    }

    private function updateExistingPhotoTypes(): void
    {
        // Обновляем тип фотографий только если есть необходимые поля
        if (Schema::hasColumn('photos', 'photoable_type') && Schema::hasColumn('photos', 'photo_type')) {
            $updated = DB::table('photos')
                ->whereNull('photo_type')
                ->update([
                    'photo_type' => DB::raw("
                        CASE
                            WHEN photoable_type = 'App\\\\Models\\\\Shift' THEN 'shift'
                            WHEN photoable_type = 'App\\\\Models\\\\VisitedLocation' THEN 'location'
                            WHEN photoable_type = 'App\\\\Models\\\\MassPersonnelReport' THEN 'mass_report'
                            WHEN photoable_type = 'App\\\\Models\\\\Expense' THEN 'expense'
                            WHEN photoable_type = 'App\\\\Models\\\\ContractorWorker' THEN 'worker'
                            ELSE 'other'
                        END
                    "),
                    'original_name' => DB::raw('COALESCE(original_name, file_name)')
                ]);
            
            if ($updated > 0) {
                echo "🔄 Обновлено типов фотографий: {$updated}\n";
            }
        }
    }
    
    private function indexExists(string $table, array $columns): bool
    {
        $indexName = $table . '_' . implode('_', $columns) . '_index';
        
        $result = DB::selectOne("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = DATABASE() 
            AND table_name = ? 
            AND index_name = ?
        ", [$table, $indexName]);
        
        return $result && $result->count > 0;
    }
};
