<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contractor_rates', function (Blueprint $table) {
            $table->dropColumn('is_anonymous');
        });
    }

    public function down()
    {
        Schema::table('contractor_rates', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false);
        });
    }
};
