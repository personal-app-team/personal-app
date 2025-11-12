<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_request_id')->constrained('work_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role_in_shift', ['executor', 'brigadier']);
            $table->enum('source', ['dispatcher', 'initiator']);
            $table->date('planned_date')->nullable();
            $table->timestamps();

            $table->unique(['work_request_id', 'user_id', 'planned_date', 'role_in_shift'], 'uq_assignment_unique_per_day');
            $table->index(['user_id', 'planned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};


