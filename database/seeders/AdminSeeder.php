<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Создание администратора системы...');
        
        // Создаем администратора
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Никита',
                'surname' => 'Майоров',
                'patronymic' => 'Андреевич',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+79999999999',
                'user_type' => 'employee',
                'email_verified_at' => now(),
            ]
        );
        
        // Назначаем роль admin
        $admin->assignRole('admin');
        
        $this->command->info('✅ Администратор создан/обновлен');
        $this->command->info('📧 Email: admin@example.com');
        $this->command->info('🔑 Пароль: password123');
        $this->command->info('👑 Назначена роль: admin');
    }
}
