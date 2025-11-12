<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            $table->date('work_date')->nullable()->after('shift_duration');
            $table->index(['work_date', 'status']);
        });
    }

    public function down()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            $table->dropIndex(['work_date', 'status']);
            $table->dropColumn('work_date');
        });
    }
};
