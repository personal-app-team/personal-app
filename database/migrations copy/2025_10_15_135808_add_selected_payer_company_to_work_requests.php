<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('work_requests', 'selected_payer_company')) {
                $table->string('selected_payer_company')->nullable()->after('work_type_id');
            }
        });
    }

    public function down()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            $table->dropColumn('selected_payer_company');
        });
    }
};
