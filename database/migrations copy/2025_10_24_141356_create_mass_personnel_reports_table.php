<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mass_personnel_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('work_requests')->onDelete('cascade');
            $table->foreignId('brigadier_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('contractor_id')->constrained('contractors')->onDelete('cascade');
            $table->foreignId('specialty_id')->constrained('specialties')->onDelete('cascade');
            $table->foreignId('work_type_id')->constrained('work_types')->onDelete('cascade');
            $table->date('work_date');
            $table->integer('workers_count');
            $table->decimal('total_hours', 8, 2);
            $table->text('worker_names')->nullable()->comment('ФИО обезличенных работников');
            $table->decimal('base_rate', 10, 2)->comment('Базовая ставка на момент создания');
            $table->decimal('compensation_amount', 10, 2)->default(0);
            $table->decimal('expenses_total', 10, 2)->default(0);
            $table->decimal('hand_amount', 10, 2)->default(0)->comment('Сумма на руки');
            $table->decimal('payout_amount', 10, 2)->default(0)->comment('Сумма к оплате');
            $table->string('status')->default('draft')
                  ->comment('draft, pending_approval, approved, completed, paid');
            $table->boolean('is_paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['work_date', 'contractor_id']);
            $table->index(['status', 'work_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mass_personnel_reports');
    }
};
