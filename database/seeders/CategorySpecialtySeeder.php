<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Specialty;
use Illuminate\Database\Seeder;

class CategorySpecialtySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📊 Создание категорий и специальностей...');
        
        $categories = [
            [
                'name' => 'Электромонтажники',
                'prefix' => 'EMT',
                'description' => 'Работы по электромонтажу',
                'is_active' => true,
            ],
            [
                'name' => 'Сантехники',
                'prefix' => 'PLM',
                'description' => 'Работы по сантехнике',
                'is_active' => true,
            ],
            [
                'name' => 'Отделочники',
                'prefix' => 'FIN',
                'description' => 'Отделочные работы',
                'is_active' => true,
            ],
            [
                'name' => 'Строители',
                'prefix' => 'BLD',
                'description' => 'Общестроительные работы',
                'is_active' => true,
            ],
            [
                'name' => 'Уборщики',
                'prefix' => 'CLN',
                'description' => 'Клининговые услуги',
                'is_active' => true,
            ],
        ];
        
        foreach ($categories as $categoryData) {
            $category = Category::create($categoryData);
            $this->command->info("✅ Категория: {$category->name} ({$category->prefix})");
            
            // Создаем специальности для каждой категории
            $specialties = $this->getSpecialtiesForCategory($category->name);
            foreach ($specialties as $specialtyName) {
                Specialty::create([
                    'name' => $specialtyName,
                    'category_id' => $category->id,
                    'base_hourly_rate' => rand(300, 800),
                    'is_active' => true,
                ]);
            }
        }
        
        $this->command->info('🎉 Категории и специальности созданы!');
    }
    
    private function getSpecialtiesForCategory(string $categoryName): array
    {
        return match($categoryName) {
            'Электромонтажники' => ['Электрик 3 разряда', 'Электрик 4 разряда', 'Электрик 5 разряда', 'Электромонтажник'],
            'Сантехники' => ['Сантехник', 'Слесарь-сантехник', 'Мастер-сантехник'],
            'Отделочники' => ['Маляр', 'Штукатур', 'Плиточник', 'Обойщик'],
            'Строители' => ['Плотник', 'Бетонщик', 'Кровельщик', 'Каменщик'],
            'Уборщики' => ['Уборщик помещений', 'Клининг-специалист', 'Мойщик окон'],
            default => ['Специалист'],
        };
    }
}
