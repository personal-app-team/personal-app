<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->string('specialization')->nullable();
            $table->boolean('is_contractor')->default(false);
            $table->foreignId('contractor_id')->nullable()->constrained('contractors');
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'specialization', 'is_contractor', 'contractor_id', 'notes']);
        });
    }
};
