<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purpose_payer_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purpose_id')->constrained()->onDelete('cascade');
            $table->string('payer_company');
            $table->text('description')->nullable();
            $table->integer('order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purpose_payer_companies');
    }
};
