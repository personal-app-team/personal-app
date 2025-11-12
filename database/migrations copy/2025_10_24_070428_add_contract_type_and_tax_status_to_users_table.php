<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('contract_type_id')->nullable()->constrained()->after('contractor_id');
            $table->foreignId('tax_status_id')->nullable()->constrained()->after('contract_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['contract_type_id']);
            $table->dropForeign(['tax_status_id']);
            $table->dropColumn(['contract_type_id', 'tax_status_id']);
        });
    }
};
