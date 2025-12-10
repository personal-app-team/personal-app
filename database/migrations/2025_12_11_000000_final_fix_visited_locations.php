<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Проверяем и добавляем workers_count
        if (Schema::hasTable('visited_locations')) {
            if (!Schema::hasColumn('visited_locations', 'workers_count')) {
                Schema::table('visited_locations', function (Blueprint $table) {
                    $table->integer('workers_count')->nullable()->after('duration_minutes');
                });
            }
            
            // 2. Добавляем индекс для полиморфной связи
            Schema::table('visited_locations', function (Blueprint $table) {
                $table->index(['visitable_type', 'visitable_id']);
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('visited_locations')) {
            // Удаляем индекс
            Schema::table('visited_locations', function (Blueprint $table) {
                $table->dropIndex(['visitable_type', 'visitable_id']);
            });
            
            // Удаляем поле workers_count, если оно существует
            if (Schema::hasColumn('visited_locations', 'workers_count')) {
                Schema::table('visited_locations', function (Blueprint $table) {
                    $table->dropColumn('workers_count');
                });
            }
        }
    }
};
