<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('work_requests');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('contractor_id')->nullable()->constrained('contractors');
            $table->string('contractor_worker_name')->nullable();
            $table->date('work_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('status', [
                'scheduled', 'started', 'completed', 'cancelled', 'no_show'
            ])->default('scheduled');
            $table->timestamp('shift_started_at')->nullable();
            $table->timestamp('shift_ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
