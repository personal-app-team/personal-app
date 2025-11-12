<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->string('contractor_code', 10)->nullable()->after('name')
                ->comment('Уникальный код подрядчика для массового персонала (ABC, XYZ, etc.)');
        });
    }

    public function down()
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropColumn('contractor_code');
        });
    }
};
