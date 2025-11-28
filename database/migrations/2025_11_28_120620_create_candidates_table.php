<?php
// database/migrations/2025_11_28_120000_create_candidates_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->foreignId('recruitment_request_id')->constrained()->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('resume_path')->nullable();
            $table->enum('source', ['hh', 'linkedin', 'recruitment', 'other'])->default('other');
            $table->date('first_contact_date')->nullable();
            $table->date('hr_contact_date')->nullable();
            $table->foreignId('expert_id')->nullable()->constrained('users')->comment('Заявитель-эксперт');
            $table->enum('status', [
                'new', 
                'contacted', 
                'sent_for_approval', 
                'approved_for_interview', 
                'in_reserve', 
                'rejected'
            ])->default('new');
            $table->text('notes')->nullable();
            $table->string('current_stage')->default('initial_contact');
            $table->foreignId('created_by_id')->constrained('users');
            $table->timestamps();
            
            $table->index(['status', 'current_stage']);
            $table->index(['recruitment_request_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('candidates');
    }
};
