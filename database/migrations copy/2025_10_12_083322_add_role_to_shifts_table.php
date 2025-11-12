<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->enum('role', ['executor', 'brigadier'])->default('executor')
                  ->after('contractor_worker_name');
            $table->index(['user_id', 'work_date', 'role']);
        });
    }

    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'work_date', 'role']);
            $table->dropColumn('role');
        });
    }
};
