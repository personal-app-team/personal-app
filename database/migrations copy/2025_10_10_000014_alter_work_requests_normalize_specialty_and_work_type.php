<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('work_requests', 'specialty_id')) {
                $table->foreignId('specialty_id')->nullable()->constrained('specialties')->nullOnDelete()->after('brigadier_id');
            }
            if (!Schema::hasColumn('work_requests', 'work_type_id')) {
                $table->foreignId('work_type_id')->nullable()->constrained('work_types')->nullOnDelete()->after('specialty_id');
            }
            // keep existing string specialization for backward compatibility
        });
    }

    public function down(): void
    {
        Schema::table('work_requests', function (Blueprint $table) {
            if (Schema::hasColumn('work_requests', 'work_type_id')) {
                $table->dropConstrainedForeignId('work_type_id');
            }
            if (Schema::hasColumn('work_requests', 'specialty_id')) {
                $table->dropConstrainedForeignId('specialty_id');
            }
        });
    }
};


