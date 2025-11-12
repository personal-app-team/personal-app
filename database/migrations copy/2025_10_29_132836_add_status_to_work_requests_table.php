<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            // Добавляем статус только если его нет
            if (!Schema::hasColumn('work_requests', 'status')) {
                $table->string('status')->default('draft')->after('additional_info')
                      ->comment('Статус заявки: draft, published, in_progress, closed, no_shifts, working, unclosed, completed, cancelled');
            }
        });
    }

    public function down()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            if (Schema::hasColumn('work_requests', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
