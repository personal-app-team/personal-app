<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // 1. Сначала добавляем новое поле gross_amount
            $table->decimal('gross_amount', 10, 2)->default(0)->after('total_amount');
            
            // 2. Добавляем поле amount_to_pay если его нет
            if (!Schema::hasColumn('shifts', 'amount_to_pay')) {
                $table->decimal('amount_to_pay', 10, 2)->default(0)->after('gross_amount');
            }
            
            // 3. Добавляем поле is_paid если его нет
            if (!Schema::hasColumn('shifts', 'is_paid')) {
                $table->boolean('is_paid')->default(false)->after('amount_to_pay');
            }
        });

        // 4. Только ПОСЛЕ создания полей переносим данные
        DB::statement('UPDATE shifts SET gross_amount = total_amount WHERE total_amount IS NOT NULL');
        
        // 5. Обновляем amount_to_pay на основе gross_amount (временно)
        DB::statement('UPDATE shifts SET amount_to_pay = gross_amount WHERE gross_amount IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Восстанавливаем total_amount из gross_amount перед удалением
            DB::statement('UPDATE shifts SET total_amount = gross_amount WHERE gross_amount IS NOT NULL');
            
            // Удаляем добавленные поля
            $table->dropColumn(['gross_amount', 'amount_to_pay', 'is_paid']);
        });
    }
};
