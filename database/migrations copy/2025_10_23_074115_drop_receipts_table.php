<?php
// database/migrations/2025_10_22_160000_drop_receipts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Удаляем таблицу receipts если она существует
        if (Schema::hasTable('receipts')) {
            Schema::drop('receipts');
        }
    }

    public function down()
    {
        // Восстановление таблицы (на случай отката)
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('original_name');
            $table->timestamps();
        });
    }
};
