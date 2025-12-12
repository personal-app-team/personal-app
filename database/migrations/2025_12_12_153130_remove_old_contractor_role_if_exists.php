<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Находим старую роль contractor
        $oldContractorRole = Role::where('name', 'contractor')->first();
        
        if ($oldContractorRole) {
            // Проверяем, есть ли пользователи с этой ролью
            $usersCount = $oldContractorRole->users()->count();
            
            if ($usersCount === 0) {
                // Если нет пользователей, удаляем роль
                $oldContractorRole->delete();
                echo "Старая роль 'contractor' удалена\n";
            } else {
                // Если есть пользователи, оставляем для обратной совместимости
                echo "Роль 'contractor' оставлена (пользователей: $usersCount)\n";
            }
        } else {
            echo "Роль 'contractor' не найдена\n";
        }
    }
    
    public function down(): void
    {
        // Восстановление (если нужно)
        Role::firstOrCreate([
            'name' => 'contractor',
            'guard_name' => 'web'
        ]);
        echo "Роль 'contractor' восстановлена\n";
    }
};
