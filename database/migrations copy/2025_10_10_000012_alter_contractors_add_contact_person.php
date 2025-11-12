<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            if (!Schema::hasColumn('contractors', 'contact_person_name')) {
                $table->string('contact_person_name')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('contractors', 'contact_person_phone')) {
                $table->string('contact_person_phone')->nullable()->after('contact_person_name');
            }
            if (!Schema::hasColumn('contractors', 'contact_person_email')) {
                $table->string('contact_person_email')->nullable()->after('contact_person_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contractors', function (Blueprint $table) {
            if (Schema::hasColumn('contractors', 'contact_person_email')) {
                $table->dropColumn('contact_person_email');
            }
            if (Schema::hasColumn('contractors', 'contact_person_phone')) {
                $table->dropColumn('contact_person_phone');
            }
            if (Schema::hasColumn('contractors', 'contact_person_name')) {
                $table->dropColumn('contact_person_name');
            }
        });
    }
};


