<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            // === УДАЛЯЕМ УСТАРЕВШИЕ ПОЛЯ (если существуют) ===
            if (Schema::hasColumn('work_requests', 'executor_type')) {
                $table->dropColumn('executor_type');
            }
            
            if (Schema::hasColumn('work_requests', 'selected_payer_company')) {
                $table->dropColumn('selected_payer_company');
            }
            
            // === ПЕРЕИМЕНОВЫВАЕМ ПОЛЕ ===
            if (Schema::hasColumn('work_requests', 'executor_names')) {
                $table->renameColumn('executor_names', 'mass_personnel_names');
            }
        });
    }

    public function down()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            // === ВОССТАНАВЛИВАЕМ ПЕРЕИМЕНОВАННОЕ ПОЛЕ ===
            if (Schema::hasColumn('work_requests', 'mass_personnel_names')) {
                $table->renameColumn('mass_personnel_names', 'executor_names');
            }
            
            // === ВОССТАНАВЛИВАЕМ УДАЛЕННЫЕ ПОЛЯ ===
            if (!Schema::hasColumn('work_requests', 'executor_type')) {
                $table->enum('executor_type', ['our_personnel', 'contractor', 'mixed'])->nullable()
                      ->comment('Тип исполнителя: наш персонал, подрядчик, смешанный');
            }
            
            if (!Schema::hasColumn('work_requests', 'selected_payer_company')) {
                $table->string('selected_payer_company')->nullable()
                      ->comment('Выбранная компания-плательщик');
            }
        });
    }
};
