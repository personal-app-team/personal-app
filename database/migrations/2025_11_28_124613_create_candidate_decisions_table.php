<?php
// database/migrations/2025_11_28_130001_create_candidate_decisions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('candidate_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->comment('Заявитель принявший решение');
            $table->enum('decision', ['reject', 'reserve', 'interview', 'other_vacancy']);
            $table->text('comment')->nullable();
            $table->date('decision_date');
            $table->timestamps();

            $table->index(['candidate_id', 'decision_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('candidate_decisions');
    }
};
