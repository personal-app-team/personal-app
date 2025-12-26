<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            // Исправляем: после brigadier_id, а не brigadier_manual
            if (!Schema::hasColumn('work_requests', 'contact_person')) {
                $table->string('contact_person')
                    ->nullable()
                    ->after('brigadier_id')
                    ->comment('Контактное лицо, если это не бригадир');
            }
        });
    }

    public function down()
    {
        Schema::table('work_requests', function (Blueprint $table) {
            $table->dropColumn('contact_person');
        });
    }
};
