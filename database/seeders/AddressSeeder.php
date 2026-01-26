<?php

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Создание шаблонов адресов...');

        $addresses = [
            ['short_name' => 'Манежная пл', 'full_address' => 'г. Москва, ЦАО, Манежная площадь', 'location_type' => 'Традиционные локации ЦД', 'is_template' => true],
            ['short_name' => 'Переход от Манежной площади к площади Революции', 'full_address' => 'г. Москва, ЦАО, Переход от Манежной площади к площади Революции', 'location_type' => 'Традиционные локации ЦД', 'is_template' => true],
            ['short_name' => 'РГБ', 'full_address' => 'г. Москва, ЦАО, ул. Воздвиженка, 3/5 (РГБ)', 'location_type' => 'Традиционные локации ЦД', 'is_template' => true],
            ['short_name' => 'Н Арбат 13-15, 19-21', 'full_address' => 'г. Москва, ЦАО, ул. Новый Арбат, вл. 13-15, вл. 19-21', 'location_type' => 'Традиционные локации ЦД', 'is_template' => true],
            ['short_name' => 'Н Арбат 8-24', 'full_address' => 'г. Москва, ЦАО, ул. Новый Арбат, вл. 8-16, 22-28', 'location_type' => 'Традиционные локации ЦД', 'is_template' => true],
            ['short_name' => 'Никольская', 'full_address' => 'г. Москва, ЦАО, ул. Никольская, вл. 25 до д. 3 стр.2', 'location_type' => 'Традиционные локации ЦД', 'is_template' => true],
            ['short_name' => 'Наутилус', 'full_address' => 'г. Москва, ЦАО, ул. Никольская, вл. 25 (площадь у ТЦ Наутилус)', 'location_type' => 'Традиционные локации ЦД', 'is_template' => true],
            ['short_name' => 'Театральная пл (Большой театр)', 'full_address' => 'г. Москва, ЦАО, Театральная площадь (у Большого театра)', 'location_type' => 'Большие площади', 'is_template' => true],
            ['short_name' => 'ЦУМ', 'full_address' => 'г. Москва, ЦАО, ул. Кузнецкий мост, вл. 7 (площадь у ЦУМа)', 'location_type' => 'Традиционные локации ЦД', 'is_template' => true],
            ['short_name' => 'Тверская ул', 'full_address' => 'г. Москва, ЦАО, ул. Тверская (городские клумбы от ул. Моховой до Пушкинской площади)', 'location_type' => 'Пешеходные улицы', 'is_template' => true],
            ['short_name' => 'Камергер', 'full_address' => 'г. Москва, ЦАО, Камергерский переулок', 'location_type' => 'Пешеходные улицы', 'is_template' => true],
            ['short_name' => 'Столешников верхний, 2-8', 'full_address' => 'г. Москва, ЦАО, Столешников переулок, д. 2-8 (от ул. Тверская до ул. Большая Дмитровка)', 'location_type' => 'Пешеходные улицы', 'is_template' => true],
            ['short_name' => 'Столешников, 10-14, нижний', 'full_address' => 'г. Москва, ЦАО, Столешников переулок, д. 10-14 (от ул. Большая Дмитровка до ул. Петровка)', 'location_type' => 'Пешеходные улицы', 'is_template' => true],
            ['short_name' => 'Кузнецкий мост нижний, 3-5', 'full_address' => 'г. Москва, ЦАО, ул. Кузнецкий мост, д.3-5 (от ул. Б. Дмитровка до ул. Петровка)', 'location_type' => 'Пешеходные улицы', 'is_template' => true],
            ['short_name' => 'Кузнецкий мост верхний, 9-13', 'full_address' => 'г. Москва, ЦАО, ул. Кузнецкий мост, д. 9-13 (от ул. Неглинная до ул. Рождественка)', 'location_type' => 'Пешеходные улицы', 'is_template' => true],
            ['short_name' => 'Вознесенский', 'full_address' => 'г. Москва, ЦАО, Вознесенский переулок, д. 21', 'location_type' => 'Пешеходные улицы', 'is_template' => true],
            ['short_name' => 'Вознесенский стена', 'full_address' => 'г. Москва, ЦАО, Вознесенский переулок, д. 22', 'location_type' => 'Пешеходные улицы', 'is_template' => true],
            ['short_name' => 'Зоопарк', 'full_address' => 'г. Москва, ЦАО, ул. Большая Грузинская, 1, стр. 1 (Московский зоопарк)', 'location_type' => 'Вертикальное озеленение', 'is_template' => true],
        ];  // ← ТОЧКА С ЗАПЯТОЙ ДОБАВЛЕНА

        foreach ($addresses as $address) {
            // Проверяем, чтобы не было дубликатов
            $exists = Address::where('full_address', $address['full_address'])
                ->where('is_template', true)
                ->exists();
                
            if (!$exists) {
                Address::create($address);
                $this->command->info("Создан адрес: {$address['full_address']}");
            } else {
                $this->command->warn("Адрес уже существует: {$address['full_address']}");
            }
        }

        $this->command->info('Адреса созданы!');
    }
}
