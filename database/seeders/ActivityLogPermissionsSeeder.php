<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ActivityLogPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Создаем разрешение для просмотра логов
        $permission = Permission::firstOrCreate([
            'name' => 'view_activity_logs',
            'guard_name' => 'web',
        ]);

        // Назначаем разрешение ролям admin и dispatcher
        $adminRole = Role::where('name', 'admin')->first();
        $dispatcherRole = Role::where('name', 'dispatcher')->first();
        
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }
        
        if ($dispatcherRole) {
            $dispatcherRole->givePermissionTo($permission);
        }
    }
}
