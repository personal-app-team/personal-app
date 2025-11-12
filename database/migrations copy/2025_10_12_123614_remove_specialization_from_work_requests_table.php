<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            $table->dropColumn('specialization');
        });
    }

    public function down()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            $table->string('specialization')->nullable();
        });
    }
};
