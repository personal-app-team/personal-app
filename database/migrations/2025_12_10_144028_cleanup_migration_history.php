<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up()
    {
        $this->info('🧹 Очистка истории миграций от проблемных записей...');

        // 1. Удаляем миграции с комментариями к удаленным таблицам
        $this->removeSpecificMigrations();

        // 2. Удаляем миграции, создающие удаленные таблицы
        $this->removeMigrationsForDroppedTables();

        // 3. Удаляем миграции-исправления, которые могли быть проблемными
        $this->removeFixMigrations();

        // 4. Дополнительные проблемные миграции из автоматической проверки
        $this->removeAdditionalProblematicMigrations();

        $this->info('✅ Очистка завершена');
    }

    private function removeSpecificMigrations()
    {
        $specificMigrations = [
            '2025_10_20_100720_add_complete_russian_comments_to_tables_and_columns',
            '2025_10_20_093511_add_russian_comments_to_tables', // Найдено автоматически
        ];

        $this->removeMigrations($specificMigrations, 'Удаленные таблицы с комментариями');
    }

    private function removeMigrationsForDroppedTables()
    {
        $migrationsForDroppedTables = [
            // Таблицы, которые были удалены
            '2025_10_06_104809_create_brigadier_assignments_table', // Удалена 2025_11_01_113542_drop_brigadier_assignment_tables
            '2025_10_10_000011_create_shift_segments_table',        // Удалена 2025_10_29_082911_drop_shift_segments_table
            '2025_10_22_141022_create_shift_settings_table',        // Удалена 2025_10_25_081350_drop_shift_settings_table
            '2025_10_23_074115_drop_receipts_table',                // receipts больше нет
            
            // Таблицы, которые были переименованы/заменены
            '2025_10_10_000004_create_expenses_table',              // Заменена на shift_expenses
        ];

        $this->removeMigrations($migrationsForDroppedTables, 'Миграции для удаленных таблиц');
    }

    private function removeFixMigrations()
    {
        $fixMigrations = [
            // Миграции-исправления, которые могли быть проблемными
            '2025_10_21_130526_mark_remove_category_migration_as_completed',
            '2025_10_21_130752_fix_remove_category_migration',
            '2025_10_21_131132_final_fix_category_removal',
            '2025_10_22_073444_update_users_and_contractors_tables_final',
            '2025_10_22_073824_fix_problem_migrations_and_update_tables',
            
            // Дублирующие миграции по shifts
            '2025_10_10_000010_alter_shifts_add_totals_and_dimensions',
            '2025_10_25_122616_add_additional_fields_to_shifts',
            '2025_10_25_123937_add_calculation_fields_to_shifts_final',
            
            // Другие потенциально проблемные миграции
            '2025_10_10_000012_alter_contractors_add_contact_person',
            '2025_10_10_000013_alter_shifts_add_time_and_travel_fields',
            '2025_10_12_083322_add_role_to_shifts_table',
            '2025_10_12_083323_add_work_date_to_work_requests_table',
            '2025_10_12_083324_add_personal_fields_to_users_table',
            '2025_10_12_083325_update_brigadier_assignments_table',
        ];

        $this->removeMigrations($fixMigrations, 'Дублирующие миграции-исправления');
    }
    
    private function removeAdditionalProblematicMigrations()
    {
        // Миграции, связанные с моделями без таблиц
        $additionalMigrations = [
            // expenses таблица заменена на shift_expenses
            '2025_10_10_000004_create_expenses_table', // уже в списке, но для ясности
            
            // contractor_workers - возможно устаревшая модель
            // Проверим, есть ли миграция для contractor_workers
        ];

        $this->removeMigrations($additionalMigrations, 'Дополнительные проблемные миграции');
    }

    private function removeMigrations(array $migrations, string $description)
    {
        $existingMigrations = DB::table('migrations')
            ->whereIn('migration', $migrations)
            ->pluck('migration')
            ->toArray();
        
        if (!empty($existingMigrations)) {
            $count = DB::table('migrations')->whereIn('migration', $existingMigrations)->delete();
            
            if ($count > 0) {
                $this->info("   Удалено {$count} миграций ({$description}):");
                foreach ($existingMigrations as $migration) {
                    $this->info("     - {$migration}");
                }
            } else {
                $this->info("   Не найдено миграций для удаления ({$description})");
            }
        } else {
            $this->info("   Нет записей для удаления ({$description})");
        }
    }
    
    private function info($message)
    {
        // Простой вывод в консоль при выполнении миграции
        if (php_sapi_name() === 'cli') {
            echo $message . PHP_EOL;
        }
    }

    public function down()
    {
        // Восстановление удаленных миграций не предусмотрено
        // Они были удалены, потому что ссылались на несуществующие таблицы
    }
};
