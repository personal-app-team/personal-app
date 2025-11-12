<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'specialty_id')) {
                $table->foreignId('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            }
            if (!Schema::hasColumn('shifts', 'work_type_id')) {
                $table->foreignId('work_type_id')->nullable()->constrained('work_types')->nullOnDelete();
            }
            if (!Schema::hasColumn('shifts', 'hourly_rate_snapshot')) {
                $table->decimal('hourly_rate_snapshot', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('shifts', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('shifts', 'expenses_total')) {
                $table->decimal('expenses_total', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('shifts', 'grand_total')) {
                $table->decimal('grand_total', 10, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['specialty_id', 'work_type_id', 'hourly_rate_snapshot', 'total_amount', 'expenses_total', 'grand_total']);
        });
    }
};


