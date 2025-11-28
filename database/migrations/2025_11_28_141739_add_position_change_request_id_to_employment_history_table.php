<?php
// database/migrations/2025_11_28_140001_add_position_change_request_id_to_employment_history_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employment_history', function (Blueprint $table) {
            $table->foreignId('position_change_request_id')
                  ->nullable()
                  ->constrained('position_change_requests')
                  ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('employment_history', function (Blueprint $table) {
            $table->dropForeign(['position_change_request_id']);
            $table->dropColumn('position_change_request_id');
        });
    }
};
