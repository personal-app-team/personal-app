<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\Specialty;

return new class extends Migration
{
    public function up(): void
    {
        // Собираем уникальные категории из specialties
        $categories = Specialty::distinct()->pluck('category')->filter();
        
        foreach ($categories as $categoryName) {
            // Создаем категорию
            $category = Category::create([
                'name' => $categoryName,
                'is_active' => true
            ]);
            
            // Обновляем специальности этой категории
            Specialty::where('category', $categoryName)->update([
                'category_id' => $category->id
            ]);
        }
    }

    public function down(): void
    {
        // При откате очищаем category_id
        Specialty::whereNotNull('category_id')->update(['category_id' => null]);
    }
};
