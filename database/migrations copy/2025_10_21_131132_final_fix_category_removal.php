<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Помечаем ВСЕ миграции связанные с category как выполненные
        $migrations = [
            '2025_10_21_125412_remove_category_from_specialties_table',
            '2025_10_21_130526_mark_remove_category_migration_as_completed',
            '2025_10_21_130752_fix_remove_category_migration'
        ];
        
        foreach ($migrations as $migration) {
            $exists = \DB::table('migrations')->where('migration', $migration)->exists();
            if (!$exists) {
                \DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => 17
                ]);
                echo "Marked migration: {$migration} as completed\n";
            }
        }
        
        // Дополнительно: если вдруг поле category существует - удаляем его безопасно
        if (Schema::hasColumn('specialties', 'category')) {
            Schema::table('specialties', function (Blueprint $table) {
                $table->dropColumn('category');
            });
            echo "Dropped category column from specialties\n";
        } else {
            echo "Category column already removed from specialties\n";
        }
    }

    public function down(): void
    {
        // Не нужно ничего делать
    }
};
