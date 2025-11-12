<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_requests', function (Blueprint $table) {
            // Заменяем specialty_id на category_id
            $table->foreignId('category_id')->nullable()->constrained()->after('brigadier_id');
            
            // Переименовываем поля
            $table->renameColumn('shift_duration', 'estimated_shift_duration');
            $table->renameColumn('comments', 'additional_info');
            
            // Добавляем новые поля
            $table->text('executor_names')->nullable()->after('executor_type');
            $table->decimal('total_worked_hours', 8, 2)->default(0)->after('executor_names');
            
            // Делаем specialty_id nullable (временно для миграции)
            $table->foreignId('specialty_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('work_requests', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            
            $table->renameColumn('estimated_shift_duration', 'shift_duration');
            $table->renameColumn('additional_info', 'comments');
            
            $table->dropColumn('executor_names');
            $table->dropColumn('total_worked_hours');
            
            $table->foreignId('specialty_id')->nullable(false)->change();
        });
    }
};
