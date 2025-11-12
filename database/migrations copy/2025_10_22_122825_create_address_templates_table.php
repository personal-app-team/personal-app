<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('address_templates', function (Blueprint $table) {
            $table->id();
            $table->text('full_address')->comment('Полный адрес');
            $table->string('location_type')->comment('Тип локации');
            $table->boolean('is_active')->default(true)->comment('Активен');
            $table->timestamps();
            
            // Индексы для оптимизации
            $table->index('is_active');
            $table->index('location_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('address_templates');
    }
};
