<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->dropColumn('request_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            $table->string('request_prefix', 10)
                  ->nullable()
                  ->after('contractor_code')
                  ->comment('Префикс для номеров заявок (3-4 символа)');
        });
    }
};