<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purpose_templates', function (Blueprint $table) {
            $table->enum('default_payer_selection_type', ['strict', 'optional', 'address_based'])
                  ->default('strict')
                  ->after('is_active');
            $table->string('default_payer_company')->nullable()->after('default_payer_selection_type');
        });
    }

    public function down(): void
    {
        Schema::table('purpose_templates', function (Blueprint $table) {
            $table->dropColumn(['default_payer_selection_type', 'default_payer_company']);
        });
    }
};
