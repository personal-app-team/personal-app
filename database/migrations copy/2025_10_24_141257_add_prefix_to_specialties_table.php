<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->string('prefix', 10)->nullable()->after('name')
                ->comment('Префикс для нумерации заявок (GARD, DECOR, etc.)');
        });
    }

    public function down()
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->dropColumn('prefix');
        });
    }
};
