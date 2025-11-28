<?php
// database/migrations/2025_11_28_130001_create_hiring_decisions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hiring_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('position_title');
            $table->foreignId('specialty_id')->nullable()->constrained();
            $table->enum('employment_type', ['temporary', 'permanent']);
            $table->enum('payment_type', ['rate', 'salary', 'combined']);
            $table->decimal('payment_value', 10, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->json('decision_makers')->nullable()->comment('Кто принимал решение [user_ids]');
            $table->foreignId('approved_by_id')->constrained('users');
            $table->enum('status', ['draft', 'approved', 'rejected'])->default('draft');
            $table->integer('trainee_period_days')->nullable()->comment('Испытательный срок в днях');
            $table->timestamps();

            $table->index(['candidate_id', 'status']);
            $table->index(['employment_type', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('hiring_decisions');
    }
};
