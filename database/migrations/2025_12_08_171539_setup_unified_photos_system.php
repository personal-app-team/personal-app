<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Если таблица photos существует (создана create_photos_table), удаляем ее
        if (Schema::hasTable('photos')) {
            Schema::dropIfExists('photos');
        }
        
        // 2. Переименовываем shift_photos в photos, если она существует
        if (Schema::hasTable('shift_photos') && !Schema::hasTable('photos')) {
            Schema::rename('shift_photos', 'photos');
        }
        
        // 3. Теперь работаем с таблицей photos (должна существовать)
        if (Schema::hasTable('photos')) {
            // Добавляем недостающие колонки
            Schema::table('photos', function (Blueprint $table) {
                // Добавляем original_name после file_name
                if (!Schema::hasColumn('photos', 'original_name')) {
                    $table->string('original_name')->nullable()->after('file_name');
                }
                
                // Добавляем photo_type после longitude
                if (!Schema::hasColumn('photos', 'photo_type')) {
                    $table->string('photo_type')->default('other')->after('longitude');
                }
                
                // Добавляем поля для верификации
                if (!Schema::hasColumn('photos', 'is_verified')) {
                    $table->boolean('is_verified')->default(false);
                }
                
                if (!Schema::hasColumn('photos', 'verified_by_id')) {
                    $table->unsignedBigInteger('verified_by_id')->nullable();
                }
                
                if (!Schema::hasColumn('photos', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable();
                }
                
                // Добавляем индексы
                $table->index('photo_type');
                $table->index('is_verified');
                $table->index('verified_by_id');
                
                // Внешний ключ
                $table->foreign('verified_by_id')
                      ->references('id')
                      ->on('users')
                      ->nullOnDelete();
            });
        } else {
            // Если таблицы photos нет (не было shift_photos), создаем ее с нуля
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
        }
        
        // 4. Обновляем типы существующих фотографий
        $this->updateExistingPhotoTypes();
    }

    public function down(): void
    {
        // При откате переименовываем обратно
        if (Schema::hasTable('photos')) {
            Schema::rename('photos', 'shift_photos');
        }
        
        // Убираем добавленные колонки из shift_photos
        Schema::table('shift_photos', function (Blueprint $table) {
            $columnsToDrop = ['original_name', 'photo_type', 'is_verified', 'verified_by_id', 'verified_at'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('shift_photos', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Убираем индексы
            $table->dropIndexIfExists('shift_photos_photo_type_index');
            $table->dropIndexIfExists('shift_photos_is_verified_index');
            $table->dropIndexIfExists('shift_photos_verified_by_id_index');
            
            // Убираем внешний ключ
            $table->dropForeignIfExists('shift_photos_verified_by_id_foreign');
        });
    }
    
    private function updateExistingPhotoTypes(): void
    {
        // Обновляем тип фотографий на основе photoable_type
        DB::table('photos')
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
                'original_name' => DB::raw('file_name')
            ]);
    }
};
