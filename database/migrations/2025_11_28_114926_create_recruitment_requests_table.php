<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('recruitment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->nullable()->constrained();
            $table->foreignId('user_id')->constrained()->comment('Заявитель');
            $table->foreignId('department_id')->constrained();
            $table->text('comment')->nullable();
            $table->integer('required_count')->default(1);
            $table->enum('employment_type', ['temporary', 'permanent']);
            $table->date('start_date');
            $table->date('end_date')->nullable()->comment('Для временных сотрудников');
            $table->foreignId('hr_responsible_id')->nullable()->constrained('users');
            $table->enum('status', ['new', 'assigned', 'in_progress', 'completed', 'cancelled'])->default('new');
            $table->enum('urgency', ['low', 'medium', 'high'])->default('medium');
            $table->date('deadline');
            $table->timestamps();
            
            // Индексы
            $table->index(['status', 'urgency']);
            $table->index(['hr_responsible_id', 'status']);
            $table->index(['deadline', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('recruitment_requests');
    }
};
