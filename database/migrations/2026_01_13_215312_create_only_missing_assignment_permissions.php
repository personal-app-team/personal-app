<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        echo "🔧 Создание ТОЛЬКО недостающих разрешений для назначений...\n";
        
        // Только три недостающих разрешения
        $missingPermissions = [
            'confirm_assignment',
            'reject_assignment',
            'create_brigadier_schedule',
        ];
        
        $createdCount = 0;
        
        foreach ($missingPermissions as $permissionName) {
            $exists = Permission::where('name', $permissionName)->exists();
            
            if (!$exists) {
                Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);
                echo "✅ Создано разрешение: {$permissionName}\n";
                $createdCount++;
            } else {
                echo "✓ Разрешение уже существует: {$permissionName}\n";
            }
        }
        
        echo "📊 Итог: создано новых разрешений - {$createdCount}\n";
    }

    public function down(): void
    {
        // Удаляем только те разрешения, которые создали в этой миграции
        $permissionsToDelete = [
            'confirm_assignment',
            'reject_assignment',
            'create_brigadier_schedule',
        ];
        
        foreach ($permissionsToDelete as $permissionName) {
            Permission::where('name', $permissionName)->delete();
        }
        
        echo "🗑️ Удалены созданные в этой миграции разрешения\n";
    }
};
