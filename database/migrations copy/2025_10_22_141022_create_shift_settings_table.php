<?php
// database/migrations/2025_10_22_141022_create_shift_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('shift_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('transport_fee', 10, 2)->default(0);
            $table->integer('no_lunch_bonus_hours')->default(1);
            $table->timestamps();
        });

        // Создаем запись по умолчанию (используем фасад DB, чтобы не зависеть от модели в миграции)
        \Illuminate\Support\Facades\DB::table('shift_settings')->insert([
            'transport_fee' => 0,
            'no_lunch_bonus_hours' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('shift_settings');
    }
};
