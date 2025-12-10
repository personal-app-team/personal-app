<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $this->info('🧹 Удаление неиспользуемых таблиц...');

        // Только таблицы, которые точно не используются
        $tablesToDrop = [
            'rates',                    // Заменена на contractor_rates
            'project_assignments',      // Не используется, есть assignments
        ];

        foreach ($tablesToDrop as $table) {
            if (Schema::hasTable($table)) {
                // Проверяем, есть ли данные
                $count = DB::table($table)->count();
                
                if ($count === 0) {
                    // Проверяем внешние ключи
                    $foreignKeys = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = '{$table}'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    
                    if (empty($foreignKeys)) {
                        Schema::dropIfExists($table);
                        $this->info("   ✅ Удалена: {$table} (0 записей)");
                    } else {
                        $this->warn("   ⚠️ Пропущена: {$table} (имеет внешние ключи)");
                    }
                } else {
                    $this->warn("   ⚠️ Пропущена: {$table} ({$count} записей)");
                }
            } else {
                $this->info("   ℹ️ Не существует: {$table}");
            }
        }

        $this->info('✅ Удаление завершено');
    }

    public function down()
    {
        $this->info('⚠️ Восстановление таблиц...');
        
        // Восстанавливаем только структуру (без данных)
        if (!Schema::hasTable('rates')) {
            Schema::create('rates', function ($table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained();
                $table->foreignId('specialty_id')->nullable()->constrained();
                $table->decimal('rate', 10, 2);
                $table->date('valid_from');
                $table->date('valid_to')->nullable();
                $table->timestamps();
            });
            $this->info('   ✅ Восстановлена: rates');
        }

        if (!Schema::hasTable('project_assignments')) {
            Schema::create('project_assignments', function ($table) {
                $table->id();
                $table->foreignId('project_id')->constrained();
                $table->foreignId('user_id')->constrained();
                $table->string('role')->nullable();
                $table->timestamps();
                $table->unique(['project_id', 'user_id']);
            });
            $this->info('   ✅ Восстановлена: project_assignments');
        }
    }

    private function info($message)
    {
        if (php_sapi_name() === 'cli') {
            echo $message . PHP_EOL;
        }
    }

    private function warn($message)
    {
        if (php_sapi_name() === 'cli') {
            echo $message . PHP_EOL;
        }
    }
};
