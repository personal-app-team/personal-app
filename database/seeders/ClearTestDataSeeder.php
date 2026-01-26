<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClearTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Временное отключение FK
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Очищаем в правильном порядке (от дочерних к родительским)
        DB::table('assignments')->truncate();
        DB::table('shifts')->truncate();
        DB::table('work_requests')->truncate();
        DB::table('address_project')->truncate();
        DB::table('addresses')->truncate();
        DB::table('purposes')->truncate();
        DB::table('projects')->truncate();
        
        // Оставляем пользователей, роли, справочники
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('Тестовые данные очищены!');
    }
}
