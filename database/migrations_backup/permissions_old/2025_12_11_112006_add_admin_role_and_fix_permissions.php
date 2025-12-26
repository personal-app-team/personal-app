<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up()
    {
        echo "🔧 Настройка ролей и разрешений...\n";
        
        // Создаем базовые разрешения
        $permissions = [
            'access_filament',
            'view_reports',
            'edit_database',
        ];
        
        // Разрешения для всех ресурсов
        $resources = [
            'user', 'role', 'permission', 'assignment', 'shift', 'work_request',
            'candidate', 'vacancy', 'recruitment_request', 'interview',
            'hiring_decision', 'department', 'employment_history',
            'contractor', 'category', 'specialty', 'activity_log',
            'expense', 'mass_personnel_report', 'photo', 'trainee_request',
            'work_request_status', 'contractor_worker', 'visited_location',
        ];
        
        $actions = ['view_any', 'view', 'create', 'update', 'delete'];
        
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = "{$action}_{$resource}";
            }
        }
        
        // Создаем разрешения
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);
        }
        
        echo "✅ Создано разрешений: " . count($permissions) . "\n";
        
        // Создаем роль admin
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);
        
        // Назначаем ВСЕ разрешения роли admin
        $adminRole->syncPermissions(Permission::all());
        
        echo "✅ Роль 'admin' создана с " . $adminRole->permissions->count() . " разрешениями\n";
        
        // Назначаем роль admin существующему администратору
        $adminUser = DB::table('users')->where('email', 'admin@example.com')->first();
        
        if ($adminUser) {
            // Удаляем все текущие роли
            DB::table('model_has_roles')->where('model_id', $adminUser->id)->delete();
            
            // Назначаем роль admin
            DB::table('model_has_roles')->insert([
                'role_id' => $adminRole->id,
                'model_type' => 'App\Models\User',
                'model_id' => $adminUser->id,
            ]);
            
            echo "✅ Роль 'admin' назначена пользователю admin@example.com\n";
        } else {
            echo "⚠️ Пользователь admin@example.com не найден, создайте его\n";
        }
    }

    public function down()
    {
        // При откате удаляем роль admin
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->delete();
        }
        
        // Удаляем разрешения (осторожно, это удалит ВСЕ разрешения)
        // Permission::where('guard_name', 'web')->delete();
    }
};
