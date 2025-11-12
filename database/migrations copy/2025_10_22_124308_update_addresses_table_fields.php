<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('addresses', function (Blueprint $table) {
            // Переименовываем name в short_name
            if (Schema::hasColumn('addresses', 'name')) {
                $table->renameColumn('name', 'short_name');
            }
            
            // Переименовываем description в location_type
            if (Schema::hasColumn('addresses', 'description')) {
                $table->renameColumn('description', 'location_type');
            }
        });
    }

    public function down()
    {
        Schema::table('addresses', function (Blueprint $table) {
            // Возвращаем обратно
            if (Schema::hasColumn('addresses', 'short_name')) {
                $table->renameColumn('short_name', 'name');
            }
            
            if (Schema::hasColumn('addresses', 'location_type')) {
                $table->renameColumn('location_type', 'description');
            }
        });
    }
};
