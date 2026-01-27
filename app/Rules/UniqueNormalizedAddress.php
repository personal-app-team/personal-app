<?php

namespace App\Rules;

use App\Models\Address;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class UniqueNormalizedAddress implements ValidationRule
{
    protected $ignoreId;
    protected $isTemplate;
    protected $projectId;

    /**
     * @param mixed $ignoreId ID адреса для исключения (при редактировании)
     * @param bool|null $isTemplate Фильтр по шаблонам
     * @param int|null $projectId Ограничение проверки в рамках проекта
     */
    public function __construct($ignoreId = null, $isTemplate = null, $projectId = null)
    {
        $this->ignoreId = $ignoreId;
        $this->isTemplate = $isTemplate;
        $this->projectId = $projectId;
    }

    /**
     * Нормализация адреса для сравнения
     */
    protected function normalize(string $address): string
    {
        // 1. Убираем лишние пробелы
        $address = trim($address);
        
        // 2. Заменяем множественные пробелы на один
        $address = preg_replace('/\s+/', ' ', $address);
        
        // 3. Приводим к нижнему регистру
        $address = mb_strtolower($address, 'UTF-8');
        
        // 4. Убираем лишние запятые и точки в конце слов
        $address = preg_replace('/([.,])(?=\s|$)/', '', $address);
        
        // 5. Стандартизируем сокращения
        $replacements = [
            'г\.' => 'г',
            'ул\.' => 'ул',
            'пр\.' => 'пр',
            'д\.' => 'д',
            'стр\.' => 'стр',
            'кв\.' => 'кв',
        ];
        
        foreach ($replacements as $search => $replace) {
            $address = preg_replace('/\b' . preg_quote($search, '/') . '\b/u', $replace, $address);
        }
        
        return trim($address);
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('Адрес должен быть строкой.');
            return;
        }
        
        $normalizedValue = $this->normalize($value);
        
        // Начинаем построение запроса
        $query = Address::query();
        
        // Если указан projectId, ищем только в рамках проекта
        if ($this->projectId !== null) {
            $query->whereHas('projects', function ($query) {
                $query->where('projects.id', $this->projectId);
            });
        }
        
        // Фильтр по шаблонам
        if ($this->isTemplate !== null) {
            $query->where('is_template', $this->isTemplate);
        }
        
        // Исключаем текущую запись при редактировании
        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }
        
        // Получаем все подходящие адреса
        $existingAddresses = $query->get();
        
        // Проверяем каждый адрес на нормализованное совпадение
        foreach ($existingAddresses as $address) {
            $normalizedExisting = $this->normalize($address->full_address);
            
            if ($normalizedExisting === $normalizedValue) {
                $message = $this->projectId 
                    ? 'Такой адрес уже существует в этом проекте.'
                    : 'Такой адрес уже существует в системе.';
                    
                $fail($message);
                return;
            }
            
            // Дополнительная проверка: если разница только в запятых/точках
            $simplifiedValue = preg_replace('/[.,\s]/', '', $normalizedValue);
            $simplifiedExisting = preg_replace('/[.,\s]/', '', $normalizedExisting);
            
            if ($simplifiedValue === $simplifiedExisting) {
                $fail('Похожий адрес уже существует (возможна разница в знаках препинания).');
                return;
            }
        }
    }
}
