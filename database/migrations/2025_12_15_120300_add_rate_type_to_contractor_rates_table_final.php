<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Проверяем, не добавлен ли уже столбец rate_type
        if (!Schema::hasColumn('contractor_rates', 'rate_type')) {
            Schema::table('contractor_rates', function (Blueprint $table) {
                // Добавляем rate_type
                $table->enum('rate_type', ['mass', 'personalized'])
                      ->default('personalized')
                      ->after('specialty_id');
                
                // Создаем новый уникальный индекс
                $table->unique(
                    ['contractor_id', 'specialty_id', 'rate_type'],
                    'contractor_rates_unique'
                );
            });
            
            // Обновляем существующие записи
            DB::table('contractor_rates')
                ->where('is_anonymous', 1)
                ->update(['rate_type' => 'mass']);
                
            DB::table('contractor_rates')
                ->where('is_anonymous', 0)
                ->update(['rate_type' => 'personalized']);
        }
    }

    public function down(): void
    {
        Schema::table('contractor_rates', function (Blueprint $table) {
            // Удаляем новый индекс
            $table->dropUnique('contractor_rates_unique');
            
            // Удаляем rate_type
            $table->dropColumn('rate_type');
        });
    }
};
