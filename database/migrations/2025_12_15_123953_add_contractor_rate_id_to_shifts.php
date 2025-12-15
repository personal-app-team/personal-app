<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Добавляем contractor_rate_id
            $table->unsignedBigInteger('contractor_rate_id')->nullable()->after('specialty_id');
            $table->foreign('contractor_rate_id')->references('id')->on('contractor_rates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['contractor_rate_id']);
            $table->dropColumn('contractor_rate_id');
        });
    }
};
