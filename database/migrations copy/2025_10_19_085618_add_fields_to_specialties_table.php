<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->string('category')->default('other')->after('description');
            $table->decimal('base_hourly_rate', 10, 2)->nullable()->after('category');
            $table->boolean('is_active')->default(true)->after('base_hourly_rate');
        });
    }

    public function down(): void
    {
        Schema::table('specialties', function (Blueprint $table) {
            $table->dropColumn(['category', 'base_hourly_rate', 'is_active']);
        });
    }
};
