<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Делаем work_request_id nullable для бригадиров
            $table->unsignedBigInteger('work_request_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Возвращаем обратно как NOT NULL
            $table->unsignedBigInteger('work_request_id')->nullable(false)->change();
        });
    }
};
