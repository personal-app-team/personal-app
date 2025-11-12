<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique()->nullable();
            $table->foreignId('initiator_id')->constrained('users');
            $table->foreignId('brigadier_id')->constrained('users');
            $table->string('specialization');
            $table->enum('executor_type', ['our_staff', 'contractor']);
            $table->integer('workers_count');
            $table->integer('shift_duration');
            $table->string('project');
            $table->string('purpose');
            $table->string('payer_company');
            $table->text('comments')->nullable();
            $table->enum('status', [
                'draft', 'published', 'in_work', 'staffed', 
                'in_progress', 'completed', 'cancelled'
            ])->default('draft');
            $table->foreignId('dispatcher_id')->nullable()->constrained('users');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('staffed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_requests');
    }
};
