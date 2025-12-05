<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_expenses', function (Blueprint $table) {
            // Добавляем поле для пользовательских типов
            if (!Schema::hasColumn('shift_expenses', 'custom_type')) {
                $table->string('custom_type')->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shift_expenses', function (Blueprint $table) {
            $table->dropColumn('custom_type');
        });
    }
};
