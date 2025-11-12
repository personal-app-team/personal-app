<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_type_id')->constrained()->onDelete('cascade');
            $table->string('name'); // НПД 4%, УСН 6%, ОСНО 20%
            $table->decimal('tax_rate', 5, 3); // 0.040, 0.060, 0.200
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Создаем базовые налоговые статусы
        DB::table('tax_statuses')->insert([
            // Самозанятый
            ['contract_type_id' => 1, 'name' => 'НПД 4%', 'tax_rate' => 0.04, 'description' => 'Налог на профессиональный доход 4%', 'is_default' => true],
            ['contract_type_id' => 1, 'name' => 'НПД 6%', 'tax_rate' => 0.06, 'description' => 'Налог на профессиональный доход 6%', 'is_default' => false],
            
            // ГПХ
            ['contract_type_id' => 2, 'name' => 'НДФЛ 13%', 'tax_rate' => 0.13, 'description' => 'Налог на доходы физ. лиц 13%', 'is_default' => true],
            
            // ИП
            ['contract_type_id' => 3, 'name' => 'УСН 6%', 'tax_rate' => 0.06, 'description' => 'Упрощенная система налогообложения 6%', 'is_default' => true],
            ['contract_type_id' => 3, 'name' => 'УСН 15%', 'tax_rate' => 0.15, 'description' => 'Упрощенная система налогообложения 15%', 'is_default' => false],
            
            // ООО
            ['contract_type_id' => 4, 'name' => 'ОСНО 20%', 'tax_rate' => 0.20, 'description' => 'Общая система налогообложения 20%', 'is_default' => true],
            ['contract_type_id' => 4, 'name' => 'УСН 15%', 'tax_rate' => 0.15, 'description' => 'Упрощенная система налогообложения 15%', 'is_default' => false],
            
            // Физ. лицо
            ['contract_type_id' => 5, 'name' => 'НДФЛ 13%', 'tax_rate' => 0.13, 'description' => 'Налог на доходы физ. лиц 13%', 'is_default' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_statuses');
    }
};
