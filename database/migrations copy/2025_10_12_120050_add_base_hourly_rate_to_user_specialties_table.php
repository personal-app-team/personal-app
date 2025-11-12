<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_specialties', function (Blueprint $table) {
            $table->decimal('base_hourly_rate', 10, 2)->nullable()->after('specialty_id');
        });
    }

    public function down()
    {
        Schema::table('user_specialties', function (Blueprint $table) {
            $table->dropColumn('base_hourly_rate');
        });
    }
};
