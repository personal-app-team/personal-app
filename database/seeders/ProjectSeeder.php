<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Создание проекта "ЗИМА В МОСКВЕ 25"...');

        // Проверяем, не существует ли уже проект
        $projectExists = Project::where('name', 'ЗИМА В МОСКВЕ 25')->exists();
        
        if ($projectExists) {
            $this->command->warn('Проект "ЗИМА В МОСКВЕ 25" уже существует. Пропускаем.');
            return;
        }

        // Создаем проект
        $project = Project::create([
            'name' => 'ЗИМА В МОСКВЕ 25',
            'description' => 'Украшение столицы к Новому Году и Рождеству. Оформление центральных улиц, площадей и пешеходных зон.',
            'start_date' => Carbon::create(2025, 12, 1),
            'end_date' => Carbon::create(2026, 2, 28),
            'default_payer_company' => null,
            'status' => 'active',
        ]);

        $this->command->info("✅ Проект создан с ID: {$project->id}");

        // Получаем ВСЕ шаблоны адресов
        $templateAddresses = Address::where('is_template', true)->get();
        
        $this->command->info("Найдено шаблонов адресов: {$templateAddresses->count()}");

        // Для каждого шаблона создаем адрес проекта (не шаблон!)
        $createdAddresses = [];
        
        foreach ($templateAddresses as $template) {
            // Проверяем, не существует ли уже адрес с таким full_address (не шаблон)
            $addressExists = Address::where('full_address', $template->full_address)
                ->where('is_template', false)
                ->exists();
                
            if ($addressExists) {
                $this->command->warn("Адрес уже существует (не шаблон): {$template->full_address}");
                continue;
            }

            // Создаем новый адрес на основе шаблона
            $address = Address::create([
                'short_name' => $template->short_name,
                'full_address' => $template->full_address,
                'location_type' => $template->location_type,
                'is_template' => false, // Это АДРЕС ПРОЕКТА, не шаблон!
            ]);

            // Прикрепляем адрес к проекту
            $project->addresses()->attach($address->id);
            
            $createdAddresses[] = $address->short_name;
            
            $this->command->info("📍 Создан и прикреплен адрес: {$address->short_name}");
        }

        $this->command->info("\n📊 ИТОГО:");
        $this->command->info("- Проект: {$project->name}");
        $this->command->info("- Прикреплено адресов: " . count($createdAddresses));
        $this->command->info("- Список адресов: " . implode(', ', array_slice($createdAddresses, 0, 5)) . (count($createdAddresses) > 5 ? '...' : ''));
        
        $this->command->info('🎉 Проект "ЗИМА В МОСКВЕ 25" успешно создан!');
    }
}
