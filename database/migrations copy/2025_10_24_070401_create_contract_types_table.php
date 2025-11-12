<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Самозанятый, ГПХ, ИП, ООО, Физ. лицо
            $table->string('code')->unique(); // self_employed, gph, ip, ooo, individual
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Создаем базовые типы договоров
        DB::table('contract_types')->insert([
            ['name' => 'Самозанятый', 'code' => 'self_employed', 'description' => 'Налог на профессиональный доход'],
            ['name' => 'Гражданско-правовой договор', 'code' => 'gph', 'description' => 'Договор ГПХ с физ. лицом'],
            ['name' => 'Индивидуальный предприниматель', 'code' => 'ip', 'description' => 'ИП на различных системах налогообложения'],
            ['name' => 'Общество с ограниченной ответственностью', 'code' => 'ooo', 'description' => 'Юридическое лицо ООО'],
            ['name' => 'Физическое лицо', 'code' => 'individual', 'description' => 'Работа по трудовому договору'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_types');
    }
};
