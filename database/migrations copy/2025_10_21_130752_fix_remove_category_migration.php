<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Помечаем обе проблемные миграции как выполненные
        $migrations = [
            '2025_10_21_125412_remove_category_from_specialties_table',
            '2025_10_21_130526_mark_remove_category_migration_as_completed'
        ];
        
        foreach ($migrations as $migration) {
            $exists = \DB::table('migrations')->where('migration', $migration)->exists();
            if (!$exists) {
                \DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => 17
                ]);
            }
        }
    }

    public function down(): void
    {
        // Не нужно ничего делать
    }
};
