<?php
// database/migrations/2025_11_28_130000_create_interviews_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->enum('interview_type', ['technical', 'managerial', 'cultural', 'combined']);
            $table->string('location')->nullable();
            $table->foreignId('interviewer_id')->constrained('users');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->enum('result', ['hire', 'reject', 'reserve', 'other_vacancy', 'trainee'])->nullable();
            $table->text('feedback')->nullable();
            $table->text('notes')->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->foreignId('created_by_id')->constrained('users');
            $table->timestamps();

            $table->index(['candidate_id', 'scheduled_at']);
            $table->index(['interviewer_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('interviews');
    }
};
