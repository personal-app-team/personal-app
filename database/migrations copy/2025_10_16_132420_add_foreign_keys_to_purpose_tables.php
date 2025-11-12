<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Для purpose_payer_companies
        Schema::table('purpose_payer_companies', function (Blueprint $table) {
            // Проверяем что колонка существует и не имеет foreign key
            $columns = Schema::getColumnListing('purpose_payer_companies');
            if (in_array('project_id', $columns)) {
                // Добавляем foreign key
                $table->foreign('project_id')
                      ->references('id')
                      ->on('projects')
                      ->onDelete('cascade');
                
                // Добавляем индекс
                $table->index(['project_id', 'purpose_id']);
            }
        });

        // Для purpose_address_rules
        Schema::table('purpose_address_rules', function (Blueprint $table) {
            // Проверяем что колонка существует и не имеет foreign key
            $columns = Schema::getColumnListing('purpose_address_rules');
            if (in_array('project_id', $columns)) {
                // Добавляем foreign key
                $table->foreign('project_id')
                      ->references('id')
                      ->on('projects')
                      ->onDelete('cascade');
                
                // Добавляем индекс
                $table->index(['project_id', 'purpose_id', 'address_id']);
            }
        });
    }

    public function down(): void
    {
        // Удаляем constraints (безопасно, даже если их нет)
        Schema::table('purpose_payer_companies', function (Blueprint $table) {
            $table->dropForeignIfExists(['project_id']);
            $table->dropIndexIfExists(['project_id', 'purpose_id']);
        });

        Schema::table('purpose_address_rules', function (Blueprint $table) {
            $table->dropForeignIfExists(['project_id']);
            $table->dropIndexIfExists(['project_id', 'purpose_id', 'address_id']);
        });
    }
};
