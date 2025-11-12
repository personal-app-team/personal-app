<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Добавляем project_id в purpose_payer_companies если его нет
        if (!Schema::hasColumn('purpose_payer_companies', 'project_id')) {
            Schema::table('purpose_payer_companies', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('id');
            });
        }

        // 2. Добавляем project_id в purpose_address_rules если его нет  
        if (!Schema::hasColumn('purpose_address_rules', 'project_id')) {
            Schema::table('purpose_address_rules', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('id');
            });
        }

        // 3. Заполняем project_id из связанных purposes
        DB::statement("
            UPDATE purpose_payer_companies 
            JOIN purposes ON purpose_payer_companies.purpose_id = purposes.id 
            SET purpose_payer_companies.project_id = purposes.project_id
            WHERE purpose_payer_companies.project_id IS NULL
        ");

        DB::statement("
            UPDATE purpose_address_rules 
            JOIN purposes ON purpose_address_rules.purpose_id = purposes.id 
            SET purpose_address_rules.project_id = purposes.project_id
            WHERE purpose_address_rules.project_id IS NULL
        ");

        // 4. Делаем колонки NOT NULL и добавляем constraints
        Schema::table('purpose_payer_companies', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
            $table->foreign('project_id')
                  ->references('id')
                  ->on('projects')
                  ->onDelete('cascade');
            $table->index(['project_id', 'purpose_id']);
        });

        Schema::table('purpose_address_rules', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
            $table->foreign('project_id')
                  ->references('id')
                  ->on('projects')
                  ->onDelete('cascade');
            $table->index(['project_id', 'purpose_id', 'address_id']);
        });
    }

    public function down(): void
    {
        Schema::table('purpose_payer_companies', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id', 'purpose_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('purpose_address_rules', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id', 'purpose_id', 'address_id']);
            $table->dropColumn('project_id');
        });
    }
};
