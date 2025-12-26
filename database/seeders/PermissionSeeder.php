<?php
// database/seeders/PermissionSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('⚠️ Разрешения генерируются Filament Shield автоматически');
        $this->command->info('📋 Для управления permissions используйте панель Shield');
    }
}
