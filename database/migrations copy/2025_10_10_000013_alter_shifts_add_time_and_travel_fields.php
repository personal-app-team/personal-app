<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'worked_minutes')) {
                $table->unsignedInteger('worked_minutes')->default(0)->after('notes');
            }
            if (!Schema::hasColumn('shifts', 'lunch_minutes')) {
                $table->unsignedInteger('lunch_minutes')->default(0)->after('worked_minutes');
            }
            if (!Schema::hasColumn('shifts', 'travel_expense_amount')) {
                $table->decimal('travel_expense_amount', 10, 2)->default(0)->after('lunch_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'travel_expense_amount')) {
                $table->dropColumn('travel_expense_amount');
            }
            if (Schema::hasColumn('shifts', 'lunch_minutes')) {
                $table->dropColumn('lunch_minutes');
            }
            if (Schema::hasColumn('shifts', 'worked_minutes')) {
                $table->dropColumn('worked_minutes');
            }
        });
    }
};


