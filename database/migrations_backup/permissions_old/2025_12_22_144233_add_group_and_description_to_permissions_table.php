<?php
// database/migrations/2025_12_18_add_group_and_description_to_permissions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем поле group если его нет
        if (!Schema::hasColumn('permissions', 'group')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('group')->nullable()->after('name')
                    ->index()
                    ->comment('Группа/модуль для фильтрации');
            });
        }

        // Убедимся что description есть и может быть NULL
        if (!Schema::hasColumn('permissions', 'description')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->text('description')->nullable()->after('guard_name');
            });
        } else {
            // Если уже есть - делаем nullable
            Schema::table('permissions', function (Blueprint $table) {
                $table->text('description')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // В обратную миграцию можно не включать удаление колонок
        // если вы хотите сохранить данные
    }
};
