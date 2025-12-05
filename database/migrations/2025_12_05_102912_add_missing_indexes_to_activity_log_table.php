<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Получаем список существующих индексов
        $existingIndexes = $this->getExistingIndexes();
        
        Schema::table('activity_log', function (Blueprint $table) use ($existingIndexes) {
            // Индекс для быстрой фильтрации по дате создания
            if (!in_array('activity_log_created_at_index', $existingIndexes)) {
                $table->index('created_at', 'activity_log_created_at_index');
            }
            
            // Индекс для быстрой фильтрации по событию
            if (!in_array('activity_log_event_index', $existingIndexes)) {
                $table->index('event', 'activity_log_event_index');
            }
            
            // Составной индекс для частых запросов в Filament
            if (!in_array('subject_type_event_created_at_index', $existingIndexes)) {
                $table->index(['subject_type', 'event', 'created_at'], 'subject_type_event_created_at_index');
            }
            
            // Индекс для поиска по batch_uuid
            if (!in_array('activity_log_batch_uuid_index', $existingIndexes)) {
                $table->index('batch_uuid', 'activity_log_batch_uuid_index');
            }
        });
    }
    
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // Удаляем только те индексы, которые мы создаем
            $indexesToDrop = [
                'activity_log_created_at_index',
                'activity_log_event_index',
                'subject_type_event_created_at_index',
                'activity_log_batch_uuid_index',
            ];
            
            foreach ($indexesToDrop as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Exception $e) {
                    // Игнорируем ошибки, если индекс не существует
                }
            }
        });
    }
    
    private function getExistingIndexes(): array
    {
        $tableName = 'activity_log';
        $databaseName = DB::getDatabaseName();
        
        $indexes = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ?
        ", [$databaseName, $tableName]);
        
        return array_column($indexes, 'INDEX_NAME');
    }
};
