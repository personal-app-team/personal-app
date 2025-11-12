<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brigadier_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brigadier_id')->constrained('users');
            $table->foreignId('initiator_id')->constrained('users');
            $table->date('assignment_date');
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            
            $table->unique(['brigadier_id', 'assignment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brigadier_assignments');
    }
};
