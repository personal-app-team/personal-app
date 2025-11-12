<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            $table->foreignId('work_type_id')->nullable()->constrained('work_types')->nullOnDelete();
            $table->decimal('hourly_rate', 10, 2);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'specialty_id', 'work_type_id', 'effective_from', 'effective_to'], 'idx_rate_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};


