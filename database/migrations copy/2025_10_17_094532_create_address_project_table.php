<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Создаем промежуточную таблицу
        Schema::create('address_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('address_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['address_id', 'project_id']);
        });

        // Переносим существующие связи из project_id в адресах
        $addresses = DB::table('addresses')->whereNotNull('project_id')->get();
        foreach ($addresses as $address) {
            DB::table('address_project')->insert([
                'address_id' => $address->id,
                'project_id' => $address->project_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Удаляем старый столбец project_id после переноса данных
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }

    public function down(): void
    {
        // Восстанавливаем старый столбец
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id');
        });

        // Переносим данные обратно
        $relations = DB::table('address_project')->get();
        foreach ($relations as $relation) {
            DB::table('addresses')
                ->where('id', $relation->address_id)
                ->update(['project_id' => $relation->project_id]);
        }

        // Добавляем внешний ключ
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });

        // Удаляем промежуточную таблицу
        Schema::dropIfExists('address_project');
    }
};
