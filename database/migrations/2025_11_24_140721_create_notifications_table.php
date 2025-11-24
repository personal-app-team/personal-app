<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            
            // Получатель уведомления
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Тип и содержание уведомления
            $table->string('type'); // Тип уведомления: trainee_request, system, etc.
            $table->string('title');
            $table->text('message');
            
            // Данные уведомления (JSON)
            $table->json('data')->nullable();
            
            // Полиморфная связь с любой сущностью
            $table->nullableMorphs('related');
            
            // Статус прочтения
            $table->timestamp('read_at')->nullable();
            
            $table->timestamps();
            
            // Индексы для производительности
            $table->index('user_id');
            $table->index('type');
            $table->index('read_at');
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
