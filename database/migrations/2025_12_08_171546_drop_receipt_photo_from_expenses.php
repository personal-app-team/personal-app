<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Удаляем колонку receipt_photo, если она существует
        if (Schema::hasColumn('expenses', 'receipt_photo')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('receipt_photo');
            });
        }
    }

    public function down(): void
    {
        // Восстанавливаем колонку при откате
        if (!Schema::hasColumn('expenses', 'receipt_photo')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('receipt_photo')->nullable()->after('amount');
            });
        }
    }
};
