<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('brigadier_assignments', function (Blueprint $table) {
            // Официальный адрес из справочника (плановый)
            $table->foreignId('planned_address_id')->nullable()->constrained('addresses')->onDelete('set null');
            
            // Неофициальный адрес (плановый)
            $table->text('planned_custom_address')->nullable();
            $table->boolean('is_custom_planned_address')->default(false);
            
            $table->index(['planned_address_id']);
        });
    }

    public function down()
    {
        Schema::table('brigadier_assignments', function (Blueprint $table) {
            $table->dropForeign(['planned_address_id']);
            $table->dropColumn([
                'planned_address_id', 
                'planned_custom_address', 
                'is_custom_planned_address'
            ]);
        });
    }
};
