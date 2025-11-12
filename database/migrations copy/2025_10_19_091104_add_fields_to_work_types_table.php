<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_types', function (Blueprint $table) {
            // УБИРАЕМ after('description') - добавляем в конец таблицы
            $table->string('category')->default('other');
            $table->boolean('requires_special_equipment')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('default_duration_hours', 8, 2)->nullable();
            $table->integer('complexity_level')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('work_types', function (Blueprint $table) {
            $table->dropColumn([
                'category', 
                'requires_special_equipment', 
                'is_active', 
                'default_duration_hours', 
                'complexity_level'
            ]);
        });
    }
};
