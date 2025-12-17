<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Временно отключаем проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // 2. Удаляем внешние ключи, которые ссылаются на contractors
        $this->dropForeignKeysReferencingContractors();
        
        // 3. Создаем временную таблицу для бэкапа (если есть данные)
        if (Schema::hasTable('contractors')) {
            $this->backupContractorsData();
        }
        
        // 4. Удаляем старую таблицу contractors
        Schema::dropIfExists('contractors');
        
        // 5. Создаем новую таблицу contractors
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            
            // Основные реквизиты компании
            $table->string('name')->comment('Название компании');
            $table->string('contractor_code', 10)->nullable()->unique()->comment('Код для номеров заявок');
            $table->string('inn', 12)->nullable()->comment('ИНН');
            $table->string('address')->nullable()->comment('Юридический адрес');
            $table->text('bank_details')->nullable()->comment('Банковские реквизиты');
            
            // Контакт руководителя
            $table->string('director')->nullable()->comment('ФИО руководителя компании');
            $table->string('director_phone')->nullable()->comment('Телефон руководителя');
            $table->string('director_email')->nullable()->comment('Email руководителя');
            
            // Контакты компании
            $table->string('company_phone')->nullable()->comment('Основной телефон компании');
            $table->string('company_email')->nullable()->comment('Основной email компании');
            
            // Налоги и договор
            $table->foreignId('contract_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_status_id')->nullable()->constrained()->nullOnDelete();
            
            // Статус и заметки
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable()->comment('Заметки и комментарии');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });
        
        // 6. Восстанавливаем данные из бэкапа (если есть)
        $this->restoreContractorsData();
        
        // 7. Восстанавливаем внешние ключи
        $this->restoreForeignKeys();
        
        // 8. Включаем проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
    
    private function dropForeignKeysReferencingContractors(): void
    {
        // Получаем все внешние ключи, которые ссылаются на таблицу contractors
        $database = DB::getDatabaseName();
        $foreignKeys = DB::select("
            SELECT 
                TABLE_NAME, 
                COLUMN_NAME, 
                CONSTRAINT_NAME 
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                REFERENCED_TABLE_NAME = 'contractors' 
                AND REFERENCED_TABLE_SCHEMA = ?
        ", [$database]);
        
        foreach ($foreignKeys as $foreignKey) {
            DB::statement("ALTER TABLE `{$foreignKey->TABLE_NAME}` DROP FOREIGN KEY `{$foreignKey->CONSTRAINT_NAME}`");
        }
    }
    
    private function backupContractorsData(): void
    {
        // Создаем временную таблицу для бэкапа
        DB::statement('CREATE TABLE IF NOT EXISTS contractors_backup LIKE contractors');
        DB::statement('INSERT INTO contractors_backup SELECT * FROM contractors');
    }
    
    private function restoreContractorsData(): void
    {
        if (!Schema::hasTable('contractors_backup')) {
            return;
        }
        
        // Преобразуем данные из старой структуры в новую
        $oldData = DB::table('contractors_backup')->get();
        
        foreach ($oldData as $item) {
            DB::table('contractors')->insert([
                'id' => $item->id,
                'name' => $item->name,
                'contractor_code' => $item->contractor_code,
                'inn' => $item->inn,
                'address' => $item->address,
                'bank_details' => $item->bank_details,
                // Преобразование старых полей в новые
                'director' => $item->contact_person ?? $item->contact_person_name ?? null,
                'director_phone' => $item->contact_person_phone ?? $item->phone ?? null,
                'director_email' => $item->contact_person_email ?? null,
                'company_phone' => $item->phone ?? null,
                'company_email' => $item->email ?? null,
                'contract_type_id' => $item->contract_type_id,
                'tax_status_id' => $item->tax_status_id,
                'is_active' => $item->is_active,
                'notes' => $item->notes,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'deleted_at' => $item->deleted_at,
            ]);
        }
        
        // Удаляем временную таблицу
        Schema::dropIfExists('contractors_backup');
    }
    
    private function restoreForeignKeys(): void
    {
        // Восстанавливаем внешние ключи для users.contractor_id
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'contractor_id')) {
            DB::statement("
                ALTER TABLE `users` 
                ADD CONSTRAINT `users_contractor_id_foreign` 
                FOREIGN KEY (`contractor_id`) 
                REFERENCES `contractors` (`id`) 
                ON DELETE SET NULL
            ");
        }
        
        // Восстанавливаем внешние ключи для contractor_rates.contractor_id
        if (Schema::hasTable('contractor_rates') && Schema::hasColumn('contractor_rates', 'contractor_id')) {
            DB::statement("
                ALTER TABLE `contractor_rates` 
                ADD CONSTRAINT `contractor_rates_contractor_id_foreign` 
                FOREIGN KEY (`contractor_id`) 
                REFERENCES `contractors` (`id`) 
                ON DELETE CASCADE
            ");
        }
        
        // Восстанавливаем внешние ключи для work_requests.contractor_id
        if (Schema::hasTable('work_requests') && Schema::hasColumn('work_requests', 'contractor_id')) {
            DB::statement("
                ALTER TABLE `work_requests` 
                ADD CONSTRAINT `work_requests_contractor_id_foreign` 
                FOREIGN KEY (`contractor_id`) 
                REFERENCES `contractors` (`id`) 
                ON DELETE SET NULL
            ");
        }
        
        // Восстанавливаем внешние ключи для shifts.contractor_id
        if (Schema::hasTable('shifts') && Schema::hasColumn('shifts', 'contractor_id')) {
            DB::statement("
                ALTER TABLE `shifts` 
                ADD CONSTRAINT `shifts_contractor_id_foreign` 
                FOREIGN KEY (`contractor_id`) 
                REFERENCES `contractors` (`id`) 
                ON DELETE SET NULL
            ");
        }
    }

    public function down(): void
    {
        // При откате просто удаляем новую таблицу и восстанавливаем из бэкапа (если он есть)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Удаляем новую таблицу
        Schema::dropIfExists('contractors');
        
        // Восстанавливаем старую таблицу из бэкапа
        if (Schema::hasTable('contractors_backup')) {
            DB::statement('CREATE TABLE contractors LIKE contractors_backup');
            DB::statement('INSERT INTO contractors SELECT * FROM contractors_backup');
            Schema::dropIfExists('contractors_backup');
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
