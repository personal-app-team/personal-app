<?php
// database/migrations/2025_10_22_143500_add_calculation_fields_to_shifts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Добавляем поля для новой логики расчета
            $table->boolean('no_lunch')->default(false)->after('lunch_minutes');
            $table->boolean('has_transport_fee')->default(false)->after('no_lunch');
            
            // Добавляем base_rate для хранения базовой ставки
            $table->decimal('base_rate', 10, 2)->default(0)->after('hourly_rate_snapshot');
        });
    }

    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['no_lunch', 'has_transport_fee', 'base_rate']);
        });
    }
};
