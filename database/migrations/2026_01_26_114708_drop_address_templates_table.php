<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Если таблица существует и есть данные - переносим их
        if (Schema::hasTable('address_templates')) {
            
            // Переносим данные из address_templates в addresses
            $templates = DB::table('address_templates')->get();
            
            foreach ($templates as $template) {
                // Проверяем, нет ли уже такого адреса
                $exists = DB::table('addresses')
                    ->where('full_address', $template->full_address)
                    ->where('is_template', true)
                    ->exists();
                    
                if (!$exists) {
                    // Используем location_type как short_name, если оно есть
                    // Иначе используем первые 50 символов full_address
                    $shortName = $template->location_type 
                        ? $template->location_type 
                        : substr($template->full_address, 0, 50);
                    
                    DB::table('addresses')->insert([
                        'short_name' => $shortName,
                        'full_address' => $template->full_address,
                        'location_type' => $template->location_type,
                        'is_template' => true,
                        'created_at' => $template->created_at,
                        'updated_at' => $template->updated_at,
                    ]);
                }
            }
            
            // Удаляем таблицу
            Schema::dropIfExists('address_templates');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // При откате создаем таблицу заново
        Schema::create('address_templates', function (Blueprint $table) {
            $table->id();
            $table->text('full_address');
            $table->string('location_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
