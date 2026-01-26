<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->boolean('is_template')
                ->default(false)
                ->after('location_type')
                ->comment('Является ли адрес шаблоном');
        });
        
        // Если есть данные в address_templates, переносим их
        if (Schema::hasTable('address_templates')) {
            DB::statement("
                INSERT INTO addresses (short_name, full_address, location_type, is_template, created_at, updated_at)
                SELECT 
                    CONCAT('Шаблон: ', id) as short_name,
                    full_address,
                    location_type,
                    true as is_template,
                    created_at,
                    updated_at
                FROM address_templates
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('is_template');
        });
    }
};
