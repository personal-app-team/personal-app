<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Сначала удаляем таблицу (она пуста, так что можно безопасно)
        Schema::dropIfExists('contractor_rates');
        
        // 2. Создаем новую таблицу с правильной структурой
        Schema::create('contractor_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contractor_id');
            $table->unsignedBigInteger('category_id');
            $table->string('specialty_name'); // Название специальности подрядчика
            $table->decimal('hourly_rate', 10, 2);
            $table->enum('rate_type', ['mass', 'personalized'])->default('personalized');
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Внешние ключи
            $table->foreign('contractor_id')->references('id')->on('contractors')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            
            // Уникальный индекс
            $table->unique(
                ['contractor_id', 'category_id', 'specialty_name', 'rate_type'],
                'contractor_rates_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_rates');
    }
};
