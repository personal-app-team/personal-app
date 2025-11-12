<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('initiator_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiator_id')->constrained('users');
            $table->foreignId('brigadier_id')->constrained('users');
            $table->boolean('is_temporary')->default(false);
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('initiator_grants');
    }
};
