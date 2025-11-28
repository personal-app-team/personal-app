<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('employment_form', ['permanent', 'temporary']);
            $table->foreignId('department_id')->constrained();
            $table->string('position');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('termination_reason', ['contract_end', 'dismissal', 'transfer', 'converted_to_permanent'])->nullable();
            $table->date('termination_date')->nullable();
            $table->foreignId('contract_type_id')->nullable()->constrained();
            $table->foreignId('tax_status_id')->nullable()->constrained();
            $table->enum('payment_type', ['salary', 'rate']);
            $table->decimal('salary_amount', 10, 2)->nullable();
            $table->boolean('has_overtime')->default(false);
            $table->decimal('overtime_rate', 10, 2)->nullable();
            $table->enum('work_schedule', ['5/2', '2/2', 'piecework']);
            $table->foreignId('primary_specialty_id')->nullable()->constrained('specialties');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->constrained('users');
            $table->timestamps();
            
            $table->index(['user_id', 'start_date']);
            $table->index(['end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employment_history');
    }
};
