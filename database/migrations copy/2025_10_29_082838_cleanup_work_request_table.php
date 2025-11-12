<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            // Только переименование и добавление недостающих полей
            if (Schema::hasColumn('work_requests', 'generated_request_number')) {
                $table->renameColumn('generated_request_number', 'request_number');
            }
            
            // Добавляем только недостающие поля
            if (!Schema::hasColumn('work_requests', 'mass_personnel_names')) {
                $table->text('mass_personnel_names')->nullable()->comment('Имена массового персонала');
            }
            
            if (!Schema::hasColumn('work_requests', 'total_worked_hours')) {
                $table->decimal('total_worked_hours', 8, 2)->default(0)->comment('Общее кол-во отработанных часов');
            }
            
            if (!Schema::hasColumn('work_requests', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained()->comment('Проект');
            }
            
            if (!Schema::hasColumn('work_requests', 'purpose_id')) {
                $table->foreignId('purpose_id')->nullable()->constrained()->comment('Назначение');
            }
            
            if (!Schema::hasColumn('work_requests', 'published_at')) {
                $table->timestamp('published_at')->nullable()->comment('Дата публикации');
            }
            
            if (!Schema::hasColumn('work_requests', 'staffed_at')) {
                $table->timestamp('staffed_at')->nullable()->comment('Дата комплектования');
            }
            
            if (!Schema::hasColumn('work_requests', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->comment('Дата завершения');
            }
        });
    }

    public function down()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            // Откат переименования
            if (Schema::hasColumn('work_requests', 'request_number')) {
                $table->renameColumn('request_number', 'generated_request_number');
            }
            
            // Удаляем добавленные поля
            $columnsToDrop = [
                'mass_personnel_names',
                'total_worked_hours', 
                'project_id',
                'purpose_id',
                'published_at',
                'staffed_at',
                'completed_at'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('work_requests', $column)) {
                    if ($column === 'project_id' || $column === 'purpose_id') {
                        $table->dropForeign(['work_requests_' . $column . '_foreign']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
