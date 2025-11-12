<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Добавляем поле prefix в categories
        Schema::table('categories', function (Blueprint $table) {
            $table->string('prefix', 10)->nullable()->after('name')
                  ->comment('Префикс для генерации номеров заявок (GARD, DECOR, etc.)');
        });

        // 2. Переносим данные из specialties в categories
        // Находим уникальные комбинации category_id и prefix
        $prefixData = DB::table('specialties')
            ->whereNotNull('prefix')
            ->whereNotNull('category_id')
            ->select('category_id', 'prefix')
            ->distinct()
            ->get();

        foreach ($prefixData as $data) {
            DB::table('categories')
                ->where('id', $data->category_id)
                ->update(['prefix' => $data->prefix]);
        }

        // 3. Удаляем поле prefix из specialties
        Schema::table('specialties', function (Blueprint $table) {
            $table->dropColumn('prefix');
        });
    }

    public function down()
    {
        // 1. Восстанавливаем поле prefix в specialties
        Schema::table('specialties', function (Blueprint $table) {
            $table->string('prefix', 10)->nullable()->after('name')
                  ->comment('Префикс для генерации номеров заявок (GARD, DECOR, etc.)');
        });

        // 2. Восстанавливаем данные из categories в specialties
        $categories = DB::table('categories')
            ->whereNotNull('prefix')
            ->get();

        foreach ($categories as $category) {
            DB::table('specialties')
                ->where('category_id', $category->id)
                ->update(['prefix' => $category->prefix]);
        }

        // 3. Удаляем поле prefix из categories
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('prefix');
        });
    }
};

