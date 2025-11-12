<?php
// database/migrations/2025_10_25_xxxxxx_add_additional_fields_to_shifts.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Добавляем недостающие поля
            $table->foreignId('address_id')->nullable()->after('work_type_id')->constrained();
            $table->string('month_period', 7)->nullable()->after('work_date'); // формат '2025-10'
            
            // Добавляем индекс для оптимизации запросов по месяцам
            $table->index(['month_period', 'user_id'], 'shifts_month_user_idx');
            $table->index(['month_period', 'contractor_id'], 'shifts_month_contractor_idx');
        });
    }

    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
            $table->dropColumn(['address_id', 'month_period']);
            $table->dropIndex('shifts_month_user_idx');
            $table->dropIndex('shifts_month_contractor_idx');
        });
    }
};
