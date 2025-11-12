<?php
// database/migrations/2025_10_22_150000_update_specialties_table_remove_code_add_category.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('specialties', function (Blueprint $table) {
            // Удаляем поле code
            if (Schema::hasColumn('specialties', 'code')) {
                $table->dropColumn('code');
            }
            
            // Добавляем поле category
            if (!Schema::hasColumn('specialties', 'category')) {
                $table->string('category')->nullable()->after('name');
            }
        });
    }

    public function down()
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->string('code')->nullable();
            $table->dropColumn('category');
        });
    }
};
