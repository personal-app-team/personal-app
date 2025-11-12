<?php
// database/migrations/2025_10_22_143000_convert_expenses_to_shift_expenses_with_structure.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Переименовываем таблицу expenses -> shift_expenses
        Schema::rename('expenses', 'shift_expenses');

        // 2. Обновляем структуру таблицы
        Schema::table('shift_expenses', function (Blueprint $table) {
            // Добавляем receipt_photo
            $table->string('receipt_photo')->nullable()->after('amount');
            
            // Переименовываем comment в description
            $table->renameColumn('comment', 'description');
            
            // Удаляем minutes (если есть в старой структуре)
            $table->dropColumn('minutes');
            
            // Можно также изменить тип на enum, но это опционально
            // $table->enum('type', ['taxi', 'other'])->change();
        });
    }

    public function down()
    {
        Schema::table('shift_expenses', function (Blueprint $table) {
            // Возвращаем оригинальную структуру
            $table->dropColumn('receipt_photo');
            $table->renameColumn('description', 'comment');
            $table->integer('minutes')->nullable();
        });

        // Возвращаем оригинальное имя таблицы
        Schema::rename('shift_expenses', 'expenses');
    }
};
