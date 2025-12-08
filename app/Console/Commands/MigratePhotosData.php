<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigratePhotosData extends Command
{
    protected $signature = 'photos:migrate-data';
    protected $description = 'Перенос данных о фотографиях в унифицированную систему';

    public function handle()
    {
        $this->info('🚀 Начало миграции данных фотографий...');
        
        // 1. Проверяем существование таблицы photos
        if (!Schema::hasTable('photos')) {
            $this->error('Таблица photos не существует!');
            return Command::FAILURE;
        }
        
        // 2. Обновляем типы фотографий (уже сделано в миграции, но на всякий случай)
        $this->updatePhotoTypes();
        
        // 3. Переносим receipt_photo из expenses (если колонка еще существует)
        $this->migrateExpenseReceipts();
        
        $this->info('✅ Миграция данных завершена!');
        
        return Command::SUCCESS;
    }
    
    private function updatePhotoTypes()
    {
        $this->info('🔄 Обновление типов фотографий...');
        
        $updated = DB::table('photos')
            ->whereNull('photo_type')
            ->update([
                'photo_type' => DB::raw("
                    CASE 
                        WHEN photoable_type = 'App\\\\Models\\\\Shift' THEN 'shift'
                        WHEN photoable_type = 'App\\\\Models\\\\VisitedLocation' THEN 'location'
                        WHEN photoable_type = 'App\\\\Models\\\\MassPersonnelReport' THEN 'mass_report'
                        WHEN photoable_type = 'App\\\\Models\\\\Expense' THEN 'expense'
                        WHEN photoable_type = 'App\\\\Models\\\\ContractorWorker' THEN 'worker'
                        ELSE 'other'
                    END
                "),
                'original_name' => DB::raw('file_name')
            ]);
            
        $this->info("Обновлено {$updated} записей");
    }
    
    private function migrateExpenseReceipts()
    {
        $this->info('🧾 Проверка фотографий расходов...');
        
        // Если колонка receipt_photo еще существует
        if (Schema::hasColumn('expenses', 'receipt_photo')) {
            $count = DB::table('expenses')
                ->whereNotNull('receipt_photo')
                ->count();
                
            $this->info("Найдено {$count} расходов с receipt_photo");
            $this->warn('Колонка receipt_photo еще существует. Удалите ее миграцией или вручную.');
        } else {
            $this->info('Колонка receipt_photo уже удалена');
        }
    }
}
