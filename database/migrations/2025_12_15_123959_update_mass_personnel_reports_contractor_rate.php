<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mass_personnel_reports', function (Blueprint $table) {
            // Удаляем старый specialty_id, если он есть (проверяем)
            if (Schema::hasColumn('mass_personnel_reports', 'specialty_id')) {
                $table->dropForeign(['specialty_id']);
                $table->dropColumn('specialty_id');
            }
            
            // Добавляем contractor_rate_id
            $table->unsignedBigInteger('contractor_rate_id')->nullable()->after('category_id');
            $table->foreign('contractor_rate_id')->references('id')->on('contractor_rates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('mass_personnel_reports', function (Blueprint $table) {
            $table->dropForeign(['contractor_rate_id']);
            $table->dropColumn('contractor_rate_id');
            
            // Восстанавливаем specialty_id
            $table->unsignedBigInteger('specialty_id')->nullable()->after('category_id');
            $table->foreign('specialty_id')->references('id')->on('specialties')->onDelete('set null');
        });
    }
};
