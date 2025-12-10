<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $this->info('🎯 Финализация pending миграций...');

        // 1. Миграции, которые нельзя выполнить (ссылаются на удаленные таблицы)
        $migrationsToMarkAsCompleted = [
            // Таблицы удалены в других миграциях
            '2025_10_06_104809_create_brigadier_assignments_table', // Удалена в 2025_11_01_113542_drop_brigadier_assignment_tables
            '2025_10_10_000004_create_expenses_table',               // Заменена на shift_expenses
            '2025_10_10_000011_create_shift_segments_table',         // Удалена в 2025_10_29_082911_drop_shift_segments_table
            '2025_10_22_141022_create_shift_settings_table',         // Удалена в 2025_10_25_081350_drop_shift_settings_table
            
            // Миграции с комментариями к удаленным таблицам
            '2025_10_20_093511_add_russian_comments_to_tables',
            
            // Дублирующие/устаревшие миграции
            '2025_10_10_000010_alter_shifts_add_totals_and_dimensions',
            '2025_10_10_000012_alter_contractors_add_contact_person',
            '2025_10_10_000013_alter_shifts_add_time_and_travel_fields',
            '2025_10_12_083322_add_role_to_shifts_table',
            '2025_10_12_083323_add_work_date_to_work_requests_table',
            '2025_10_12_083324_add_personal_fields_to_users_table',
            '2025_10_12_083325_update_brigadier_assignments_table',
            '2025_10_21_130526_mark_remove_category_migration_as_completed',
            '2025_10_21_130752_fix_remove_category_migration',
            '2025_10_21_131132_final_fix_category_removal',
            '2025_10_22_073444_update_users_and_contractors_tables_final',
            '2025_10_22_073824_fix_problem_migrations_and_update_tables',
            '2025_10_23_074115_drop_receipts_table',
            '2025_10_25_122616_add_additional_fields_to_shifts',
            '2025_10_25_123937_add_calculation_fields_to_shifts_final',
        ];

        // 2. Миграции, которые можно безопасно выполнить
        $migrationsToExecute = [
            '2025_12_10_152447_drop_unused_tables',
            '2025_12_10_170000_convert_shift_expenses_to_expenses',
        ];

        $batch = DB::table('migrations')->max('batch') + 1;
        $markedCount = 0;
        $executedCount = 0;

        // Отмечаем проблемные миграции как выполненными
        foreach ($migrationsToMarkAsCompleted as $migration) {
            if (!DB::table('migrations')->where('migration', $migration)->exists()) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $batch
                ]);
                $this->info("   ✅ Отмечена: {$migration}");
                $markedCount++;
            }
        }

        $this->info("📊 Отмечено миграций: {$markedCount}");
        $this->info("📊 Batch: {$batch}");
    }

    public function down()
    {
        $this->info('⏪ Удаление записей о проблемных миграциях...');
        
        $migrationsToRemove = [
            '2025_10_06_104809_create_brigadier_assignments_table',
            '2025_10_10_000004_create_expenses_table',
            '2025_10_10_000011_create_shift_segments_table',
            '2025_10_22_141022_create_shift_settings_table',
            '2025_10_20_093511_add_russian_comments_to_tables',
            '2025_10_10_000010_alter_shifts_add_totals_and_dimensions',
            '2025_10_10_000012_alter_contractors_add_contact_person',
            '2025_10_10_000013_alter_shifts_add_time_and_travel_fields',
            '2025_10_12_083322_add_role_to_shifts_table',
            '2025_10_12_083323_add_work_date_to_work_requests_table',
            '2025_10_12_083324_add_personal_fields_to_users_table',
            '2025_10_12_083325_update_brigadier_assignments_table',
            '2025_10_21_130526_mark_remove_category_migration_as_completed',
            '2025_10_21_130752_fix_remove_category_migration',
            '2025_10_21_131132_final_fix_category_removal',
            '2025_10_22_073444_update_users_and_contractors_tables_final',
            '2025_10_22_073824_fix_problem_migrations_and_update_tables',
            '2025_10_23_074115_drop_receipts_table',
            '2025_10_25_122616_add_additional_fields_to_shifts',
            '2025_10_25_123937_add_calculation_fields_to_shifts_final',
        ];

        $deleted = DB::table('migrations')
            ->whereIn('migration', $migrationsToRemove)
            ->delete();

        $this->info("✅ Удалено записей: {$deleted}");
    }

    private function info($message)
    {
        if (php_sapi_name() === 'cli') {
            echo $message . PHP_EOL;
        }
    }
};
