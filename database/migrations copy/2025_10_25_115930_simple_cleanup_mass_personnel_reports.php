<?php
// database/migrations/2025_10_25_xxxxxx_simple_cleanup_mass_personnel_reports.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Просто удаляем старые поля без переименования
        Schema::table('mass_personnel_reports', function (Blueprint $table) {
            $oldColumns = ['brigadier_id', 'contractor_id', 'specialty_id'];
            foreach ($oldColumns as $column) {
                if (Schema::hasColumn('mass_personnel_reports', $column)) {
                    // Пытаемся удалить foreign key
                    try {
                        $table->dropForeign([$column]);
                    } catch (\Exception $e) {
                        // Игнорируем ошибку
                    }
                    $table->dropColumn($column);
                }
            }
        });

        // Создаем таблицу для локаций
        Schema::create('mass_personnel_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('mass_personnel_reports')->onDelete('cascade');
            $table->foreignId('address_id')->nullable()->constrained();
            $table->string('custom_address')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->string('photo_path')->nullable();
            $table->boolean('is_last_location')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mass_personnel_locations');
        
        // Восстанавливаем структуру
        Schema::table('mass_personnel_reports', function (Blueprint $table) {
            $table->foreignId('brigadier_id')->nullable()->constrained('users');
            $table->foreignId('contractor_id')->nullable()->constrained();
            $table->foreignId('specialty_id')->nullable()->constrained();
        });
    }
};
