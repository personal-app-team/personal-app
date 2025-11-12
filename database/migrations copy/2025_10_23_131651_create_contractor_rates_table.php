<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contractor_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->onDelete('cascade');
            $table->foreignId('specialty_id')->constrained()->onDelete('cascade');
            $table->decimal('hourly_rate', 10, 2);
            $table->boolean('is_anonymous')->default(false); // true - обезличенный персонал
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Уникальность: подрядчик + специальность + тип
            $table->unique(['contractor_id', 'specialty_id', 'is_anonymous']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contractor_rates');
    }
};
