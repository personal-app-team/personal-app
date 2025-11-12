<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Проверяем и добавляем только те поля, которых нет
            
            if (!Schema::hasColumn('assignments', 'assignment_type')) {
                $table->string('assignment_type')->default('work_request')
                      ->comment('brigadier_schedule, work_request, mass_personnel');
            }
            
            if (!Schema::hasColumn('assignments', 'planned_start_time')) {
                $table->time('planned_start_time')->nullable()
                      ->comment('Планируемое время начала работы');
            }
            
            if (!Schema::hasColumn('assignments', 'planned_duration_hours')) {
                $table->decimal('planned_duration_hours', 4, 1)->nullable()
                      ->comment('Планируемая продолжительность смены');
            }
            
            if (!Schema::hasColumn('assignments', 'assignment_comment')) {
                $table->text('assignment_comment')->nullable()
                      ->comment('Комментарий к назначению');
            }
            
            if (!Schema::hasColumn('assignments', 'status')) {
                $table->string('status')->default('pending')
                      ->comment('pending, confirmed, rejected, completed');
            }
            
            if (!Schema::hasColumn('assignments', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable();
            }
            
            if (!Schema::hasColumn('assignments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }
            
            if (!Schema::hasColumn('assignments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }
            
            if (!Schema::hasColumn('assignments', 'planned_address_id')) {
                $table->foreignId('planned_address_id')->nullable()->constrained('addresses')
                      ->comment('Планируемый адрес работы');
            }
            
            if (!Schema::hasColumn('assignments', 'planned_custom_address')) {
                $table->text('planned_custom_address')->nullable()
                      ->comment('Неофициальный адрес');
            }
            
            if (!Schema::hasColumn('assignments', 'is_custom_planned_address')) {
                $table->boolean('is_custom_planned_address')->default(false)
                      ->comment('Использовать неофициальный адрес');
            }
            
            if (!Schema::hasColumn('assignments', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->constrained('shifts')
                      ->comment('Созданная смена на основе назначения');
            }
            
            // Добавляем индексы только если соответствующие колонки существуют
            if (Schema::hasColumn('assignments', 'assignment_type') && Schema::hasColumn('assignments', 'status')) {
                $table->index(['assignment_type', 'status']);
            }
            
            if (Schema::hasColumn('assignments', 'assignment_number')) {
                $table->index('assignment_number');
            }
        });
    }

    public function down()
    {
        // В down методе просто удаляем все добавленные поля
        // Это безопасно, так как проверяем существование в up
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn([
                'assignment_type',
                'planned_start_time',
                'planned_duration_hours', 
                'assignment_comment',
                'status',
                'confirmed_at',
                'rejected_at',
                'rejection_reason',
                'planned_address_id',
                'planned_custom_address',
                'is_custom_planned_address',
                'shift_id'
            ]);
        });
    }
};
