<?php
// database/migrations/2025_10_25_xxxxxx_final_cleanup_work_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Удаляем foreign key для specialty_id если существует
        Schema::table('work_requests', function (Blueprint $table) {
            // Проверяем существует ли foreign key (простой способ)
            $foreignKeys = $this->getForeignKeys('work_requests');
            if (in_array('work_requests_specialty_id_foreign', $foreignKeys)) {
                $table->dropForeign(['specialty_id']);
            }
        });

        // Удаляем ненужные колонки
        Schema::table('work_requests', function (Blueprint $table) {
            $columnsToDrop = [
                'request_number', 
                'mass_personnel_names', 
                'total_worked_hours',
                'status', 
                'published_at', 
                'staffed_at', 
                'completed_at',
                'project',        
                'purpose',        
                'payer_company',
                'specialty_id'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('work_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            // Восстанавливаем структуру
            $table->string('request_number')->nullable();
            $table->text('mass_personnel_names')->nullable();
            $table->decimal('total_worked_hours', 8, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('staffed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('project')->nullable();
            $table->string('purpose')->nullable();
            $table->string('payer_company')->nullable();
            $table->foreignId('specialty_id')->nullable()->constrained();
        });
    }

    /**
     * Получить список foreign keys для таблицы
     */
    private function getForeignKeys($tableName)
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $foreignKeys = $connection->select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $tableName]);
        
        return array_column($foreignKeys, 'CONSTRAINT_NAME');
    }
};
