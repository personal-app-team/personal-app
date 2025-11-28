<?php
// database/migrations/2025_11_28_130000_create_candidate_status_histories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('candidate_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->enum('status', [
                'new', 
                'contacted', 
                'sent_for_approval', 
                'approved_for_interview', 
                'in_reserve', 
                'rejected'
            ]);
            $table->text('comment')->nullable();
            $table->foreignId('changed_by_id')->constrained('users');
            $table->string('previous_status')->nullable();
            $table->timestamps();

            $table->index(['candidate_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('candidate_status_histories');
    }
};
