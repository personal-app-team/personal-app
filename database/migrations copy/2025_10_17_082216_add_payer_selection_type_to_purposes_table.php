<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purposes', function (Blueprint $table) {
            $table->enum('payer_selection_type', ['strict', 'optional', 'address_based'])
                  ->default('strict')
                  ->after('is_active');
            $table->string('default_payer_company')->nullable()->after('payer_selection_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purposes', function (Blueprint $table) {
            $table->dropColumn(['payer_selection_type', 'default_payer_company']);
        });
    }
};
