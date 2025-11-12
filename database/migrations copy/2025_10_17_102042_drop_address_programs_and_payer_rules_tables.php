<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Удаляем таблицы в правильном порядке (сначала зависимые)
        if (Schema::hasTable('payer_rules')) {
            Schema::dropIfExists('payer_rules');
        }
        
        if (Schema::hasTable('address_programs')) {
            Schema::dropIfExists('address_programs');
        }
    }

    public function down(): void
    {
        // Восстановление не предусмотрено - это сознательное удаление
    }
};
