<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Проверяем нет ли уже этой миграции
        $exists = \DB::table('migrations')
            ->where('migration', '2025_10_21_125412_remove_category_from_specialties_table')
            ->exists();
            
        if (!$exists) {
            \DB::table('migrations')->insert([
                'migration' => '2025_10_21_125412_remove_category_from_specialties_table',
                'batch' => 17
            ]);
        }
    }

    public function down(): void
    {
        // Не нужно ничего делать в down
    }
};
