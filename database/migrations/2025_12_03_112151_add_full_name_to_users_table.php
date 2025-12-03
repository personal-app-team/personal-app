<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Добавляем вычисляемое поле (stored generated column)
            $table->string('full_name')->virtualAs(
                "CONCAT(
                    COALESCE(NULLIF(TRIM(surname), ''), ''),
                    CASE 
                        WHEN TRIM(surname) != '' AND (TRIM(name) != '' OR TRIM(patronymic) != '') THEN ' '
                        ELSE ''
                    END,
                    COALESCE(NULLIF(TRIM(name), ''), ''),
                    CASE 
                        WHEN TRIM(patronymic) != '' THEN ' '
                        ELSE ''
                    END,
                    COALESCE(NULLIF(TRIM(patronymic), ''), '')
                )"
            )->nullable()->after('patronymic');
            
            // Добавляем индекс для поиска
            $table->index('full_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
};
