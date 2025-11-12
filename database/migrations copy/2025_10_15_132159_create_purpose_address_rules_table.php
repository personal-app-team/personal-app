<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purpose_address_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purpose_id')->constrained()->onDelete('cascade');
            $table->foreignId('address_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('payer_company');
            $table->integer('priority')->default(1);
            $table->timestamps();

            $table->unique(['purpose_id', 'address_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purpose_address_rules');
    }
};
