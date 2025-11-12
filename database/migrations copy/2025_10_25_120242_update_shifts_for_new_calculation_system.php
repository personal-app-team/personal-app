<?php
// database/migrations/2025_10_25_xxxxxx_update_shifts_for_new_calculation_system.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Добавляем новые поля
            $table->decimal('compensation_amount', 10, 2)->default(0)->after('travel_expense_amount');
            $table->text('compensation_description')->nullable()->after('compensation_amount');
            
            // Обновляем статусы согласно новому workflow
            $table->enum('status', ['scheduled', 'active', 'pending_approval', 'completed', 'paid', 'cancelled'])->default('scheduled')->change();
            
            // Удаляем устаревшие поля
            $columnsToDrop = [
                'contractor_worker_name',
                'shift_started_at', 
                'shift_ended_at',
                'lunch_minutes',
                'no_lunch',
                'has_transport_fee', 
                'travel_expense_amount',
                'hourly_rate_snapshot',
                'grand_total'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('shifts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Восстанавливаем удаленные поля
            $table->string('contractor_worker_name')->nullable();
            $table->timestamp('shift_started_at')->nullable();
            $table->timestamp('shift_ended_at')->nullable();
            $table->integer('lunch_minutes')->default(0);
            $table->boolean('no_lunch')->default(false);
            $table->boolean('has_transport_fee')->default(false);
            $table->decimal('travel_expense_amount', 10, 2)->default(0);
            $table->decimal('hourly_rate_snapshot', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            
            // Возвращаем старые статусы
            $table->enum('status', ['scheduled','started','completed','cancelled','no_show'])->default('scheduled')->change();
            
            // Удаляем добавленные поля
            $table->dropColumn(['compensation_amount', 'compensation_description']);
        });
    }
};
