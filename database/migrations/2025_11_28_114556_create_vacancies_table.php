<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->enum('employment_type', ['temporary', 'permanent']);
            $table->foreignId('department_id')->constrained();
            $table->foreignId('created_by_id')->constrained('users');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->timestamps();
            
            $table->index(['status', 'employment_type']);
            $table->index(['department_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vacancies');
    }
};
