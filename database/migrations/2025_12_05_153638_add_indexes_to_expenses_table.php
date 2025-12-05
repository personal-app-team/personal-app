<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                // Проверяем существование индексов перед созданием
                $indexes = DB::select('SHOW INDEX FROM expenses');
                $indexNames = array_column($indexes, 'Key_name');
                
                // Добавляем индекс для полиморфной связи если его нет
                if (!in_array('expenses_expensable_type_expensable_id_index', $indexNames)) {
                    $table->index(['expensable_type', 'expensable_id']);
                }
                
                // Добавляем индекс для типа если его нет
                if (!in_array('expenses_type_index', $indexNames)) {
                    $table->index('type');
                }
                
                // Добавляем индекс для суммы если его нет
                if (!in_array('expenses_amount_index', $indexNames)) {
                    $table->index('amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                // Удаляем индексы при откате
                $indexes = ['expenses_expensable_type_expensable_id_index', 'expenses_type_index', 'expenses_amount_index'];
                foreach ($indexes as $index) {
                    try {
                        $table->dropIndex($index);
                    } catch (\Exception $e) {
                        // Игнорируем если индекс не существует
                    }
                }
            });
        }
    }
};
