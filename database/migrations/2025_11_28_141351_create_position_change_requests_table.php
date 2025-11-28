<?php
// database/migrations/2025_11_28_140000_create_position_change_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('position_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->comment('Сотрудник');
            $table->string('current_position');
            $table->string('new_position');
            $table->enum('current_payment_type', ['rate', 'salary', 'combined']);
            $table->enum('new_payment_type', ['rate', 'salary', 'combined']);
            $table->decimal('current_payment_value', 10, 2);
            $table->decimal('new_payment_value', 10, 2);
            $table->text('reason');
            $table->foreignId('requested_by_id')->constrained('users')->comment('Кто запросил');
            $table->foreignId('approved_by_id')->nullable()->constrained('users')->comment('Кто утвердил');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->date('effective_date');
            $table->json('notification_users')->nullable()->comment('Кого уведомить [user_ids]');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['effective_date', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('position_change_requests');
    }
};
