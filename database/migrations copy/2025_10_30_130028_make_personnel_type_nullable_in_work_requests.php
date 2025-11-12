<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('work_requests', 'personnel_type')) {
            Schema::table('work_requests', function (Blueprint $table) {
                $table->enum('personnel_type', ['personalized', 'mass'])->nullable()->change();
            });
        } else {
            Schema::table('work_requests', function (Blueprint $table) {
                $table->enum('personnel_type', ['personalized', 'mass'])->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('work_requests', 'personnel_type')) {
            Schema::table('work_requests', function (Blueprint $table) {
                $table->enum('personnel_type', ['personalized', 'mass'])->default('personalized')->change();
            });
        }
    }
};
