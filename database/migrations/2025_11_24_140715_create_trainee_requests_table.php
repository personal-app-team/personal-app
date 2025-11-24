<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainee_requests', function (Blueprint $table) {
            $table->id();
            
            // Инициатор запроса
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Данные кандидата
            $table->string('candidate_name');
            $table->string('candidate_email');
            $table->string('candidate_position');
            $table->foreignId('specialty_id')->constrained()->onDelete('cascade');
            
            // Условия стажировки
            $table->boolean('is_paid')->default(false);
            $table->decimal('proposed_rate', 10, 2)->nullable();
            $table->integer('duration_days')->default(7); // 1-7 дней
            
            // Статус workflow
            $table->enum('status', [
                'pending',       // Ожидает HR
                'hr_approved',   // HR подтвердил
                'hr_rejected',   // HR отклонил
                'manager_approved', // Менеджер подтвердил
                'active',        // Стажировка активна
                'completed',     // Стажировка завершена
                'hired',         // Принят на работу
                'rejected'       // Отказано
            ])->default('pending');
            
            // HR approval
            $table->text('hr_comment')->nullable();
            $table->foreignId('hr_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('hr_approved_at')->nullable();
            
            // Manager approval
            $table->text('manager_comment')->nullable();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('manager_approved_at')->nullable();
            
            // Даты стажировки
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // Созданный пользователь-стажер
            $table->foreignId('trainee_user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Автоматизация
            $table->timestamp('decision_required_at')->nullable(); // Когда требуется решение
            $table->timestamp('blocked_at')->nullable(); // Когда стажер заблокирован
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы для производительности
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index('decision_required_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainee_requests');
    }
};
