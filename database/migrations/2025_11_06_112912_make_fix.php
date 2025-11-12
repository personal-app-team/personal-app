<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('work_requests', 'request_number')) {
                $table->string('request_number')->nullable()->after('id')->index()
                      ->comment('Номер заявки (формат WR-{id})');
            }
        });

        // Заполнить для существующих записей простым значением, если пусто
        $rows = DB::table('work_requests')->whereNull('request_number')->orWhere('request_number', '')->get();
        foreach ($rows as $r) {
            DB::table('work_requests')->where('id', $r->id)
                ->update(['request_number' => 'WR-' . $r->id]);
        }
    }

    public function down()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            if (Schema::hasColumn('work_requests', 'request_number')) {
                $table->dropIndex(['request_number']);
                $table->dropColumn('request_number');
            }
        });
    }
};