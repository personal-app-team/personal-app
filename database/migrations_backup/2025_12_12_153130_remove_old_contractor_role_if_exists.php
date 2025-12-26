<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        echo "🔄 Проверка старой роли contractor...\n";
        
        // Проверяем, существует ли таблица roles
        if (!Schema::hasTable('roles')) {
            echo "⚠️ Таблица roles не существует. Пропускаем миграцию.\n";
            return;
        }
        
        try {
            // Проверяем, существует ли роль contractor
            $contractorRole = Role::where('name', 'contractor')->first();
            
            if ($contractorRole) {
                echo "🗑️ Найдена старая роль 'contractor'. Удаляем...\n";
                
                // Удаляем все связи из model_has_roles
                DB::table('model_has_roles')->where('role_id', $contractorRole->id)->delete();
                
                // Удаляем все связи из role_has_permissions
                DB::table('role_has_permissions')->where('role_id', $contractorRole->id)->delete();
                
                // Удаляем саму роль
                $contractorRole->delete();
                
                echo "✅ Старая роль 'contractor' удалена.\n";
            } else {
                echo "ℹ️ Роль 'contractor' не найдена, ничего не делаем.\n";
            }
        } catch (\Exception $e) {
            echo "⚠️ Ошибка при удалении роли: " . $e->getMessage() . "\n";
            echo "⚠️ Продолжаем миграцию...\n";
        }
    }

    public function down(): void
    {
        // При откате восстанавливаем роль contractor (но только если она не существует)
        if (Schema::hasTable('roles')) {
            try {
                if (!Role::where('name', 'contractor')->exists()) {
                    Role::create([
                        'name' => 'contractor',
                        'guard_name' => 'web',
                        'description' => 'Подрядчик (старая роль)'
                    ]);
                    echo "✅ Восстановлена роль 'contractor'.\n";
                }
            } catch (\Exception $e) {
                echo "⚠️ Ошибка при восстановлении роли: " . $e->getMessage() . "\n";
            }
        }
    }
};
