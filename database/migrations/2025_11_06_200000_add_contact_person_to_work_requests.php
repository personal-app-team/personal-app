<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('work_requests', 'contact_person')) {
                $table->string('contact_person', 255)->nullable()->after('brigadier_manual')
                      ->comment('Контактное лицо, если это не бригадир');
            }
        });

        // на всякий случай заполнить пустые значения NULL -> ''
        DB::table('work_requests')->whereNull('contact_person')->update(['contact_person' => '']);
    }

    public function down()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            if (Schema::hasColumn('work_requests', 'contact_person')) {
                $table->dropColumn('contact_person');
            }
        });
    }
};