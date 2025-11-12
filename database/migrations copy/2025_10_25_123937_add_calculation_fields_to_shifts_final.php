<?php
// database/migrations/2025_10_25_xxxxxx_add_calculation_fields_to_shifts_final.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Добавляем поля для новой системы расчетов
            $table->decimal('hand_amount', 10, 2)->default(0)->after('base_rate');
            $table->decimal('payout_amount', 10, 2)->default(0)->after('hand_amount');
            $table->decimal('tax_amount', 10, 2)->default(0)->after('payout_amount');
            
            // Удаляем старые поля расчетов
            $columnsToDrop = ['gross_amount', 'total_amount', 'amount_to_pay'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('shifts', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Добавляем индекс для статусов
            $table->index(['status', 'work_date'], 'shifts_status_date_idx');
        });
    }

    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Восстанавливаем старые поля
            $table->decimal('gross_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('amount_to_pay', 10, 2)->default(0);
            
            // Удаляем новые поля
            $table->dropColumn(['hand_amount', 'payout_amount', 'tax_amount']);
            
            // Удаляем индекс
            $table->dropIndex('shifts_status_date_idx');
        });
    }
};
