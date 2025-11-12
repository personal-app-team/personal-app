<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_types', function (Blueprint $table) {
            // Удаляем ненужные поля
            $table->dropColumn([
                'premium_rate',
                'category', 
                'requires_special_equipment',
                'default_duration_hours',
                'complexity_level'
            ]);
        });
    }

    public function down()
    {
        Schema::table('work_types', function (Blueprint $table) {
            // Восстанавливаем поля при откате
            $table->decimal('premium_rate', 8, 2)->nullable();
            $table->string('category')->nullable();
            $table->boolean('requires_special_equipment')->default(false);
            $table->decimal('default_duration_hours', 8, 2)->nullable();
            $table->integer('complexity_level')->nullable();
        });
    }
};
