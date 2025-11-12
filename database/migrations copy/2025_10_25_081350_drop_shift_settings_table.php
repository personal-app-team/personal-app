<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('shift_settings');
    }

    public function down()
    {
        // Восстанавливаем таблицу на случай отката
        Schema::create('shift_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('transport_surcharge', 10, 2)->default(0);
            $table->integer('bonus_hours')->default(0);
            $table->timestamps();
        });
    }
};
