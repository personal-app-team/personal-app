<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class Contractor extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'contractor_code',
        'contact_person',
        'contact_person_phone',
        'contact_person_email',
        'phone',
        'email',
        'user_id',
        'contract_type_id',
        'tax_status_id',
        'address',
        'inn',
        'bank_details',
        'specializations',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'specializations' => 'array',
        'is_active' => 'boolean',
    ];

    // === МЕТОД ДЛЯ ACTIVITYLOG ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'contractor_code',
                'contact_person',
                'contact_person_phone',
                'contact_person_email',
                'phone',
                'email',
                'user_id',
                'contract_type_id',
                'tax_status_id',
                'address',
                'inn',
                'bank_details',
                'specializations',
                'notes',
                'is_active',
            ])
            ->logOnlyDirty()                   // Только измененные поля
            ->dontSubmitEmptyLogs()           // Не сохранять пустые логи
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => '🏢 Подрядчик создан',
                'updated' => '✏️ Подрядчик обновлен',
                'deleted' => '🗑️ Подрядчик удален',
                'restored' => '♻️ Подрядчик восстановлен',
                default => "🏢 Подрядчик был {$eventName}",
            })
            ->useLogName('contractors')       // Категория лога
            ->submitEmptyLogs(false);         // Явно указываем не сохранять пустые логи
    }

    // === ОПЦИОНАЛЬНО: ДОБАВЛЕНИЕ ДОПОЛНИТЕЛЬНЫХ ДАННЫХ В ЛОГ ===
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'executors_count' => $this->executors()->count(),
            'has_active_rates' => $this->contractorRates()->where('is_active', true)->exists(),
            'contract_type' => $this->contractType?->name ?? 'Не указан',
            'tax_status' => $this->taxStatus?->name ?? 'Не указан',
            'is_active_display' => $this->is_active ? 'Активен' : 'Неактивен',
        ]);
    }

    // === АВТОМАТИЧЕСКАЯ ГЕНЕРАЦИЯ КОДА ===
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contractor) {
            if (empty($contractor->contractor_code)) {
                $contractor->contractor_code = static::generateContractorCode($contractor->name);
            }
        });

        static::updating(function ($contractor) {
            // Обновляем код только если изменилось имя И код не меняли вручную
            if ($contractor->isDirty('name') && !$contractor->isDirty('contractor_code')) {
                $contractor->contractor_code = static::generateContractorCode($contractor->name);
            }
        });
    }

    public static function generateContractorCode($name): string
    {
        // Убираем ООО, ИП и т.д.
        $ignoreWords = ['ооо', 'ип', 'зао', 'оао', 'llc', 'inc', 'ltd'];
        
        // Транслитерация кириллицы в латиницу
        $transliterationMap = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'E', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'TS', 'Ч' => 'CH',
            'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA'
        ];
        
        // Транслитерируем название
        $transliterated = strtr(mb_strtolower($name, 'UTF-8'), $transliterationMap);
        
        // Разбиваем на слова
        $words = array_filter(explode(' ', preg_replace('/[^a-zA-Z0-9\s]/u', '', $transliterated)));
        
        $code = '';
        foreach ($words as $word) {
            $cleanWord = trim($word);
            if (!empty($cleanWord) && !in_array(mb_strtolower($cleanWord), $ignoreWords)) {
                $code .= strtoupper(substr($cleanWord, 0, 1));
                if (strlen($code) >= 3) break; // Максимум 3 буквы
            }
        }
        
        // Если код слишком короткий, берем первые 3 символа из транслитерированного названия
        if (strlen($code) < 3) {
            $cleaned = preg_replace('/[^a-zA-Z]/u', '', $transliterated);
            $code = strtoupper(substr($cleaned, 0, 3));
        }
        
        // Проверяем уникальность
        $counter = 1;
        $originalCode = $code;
        
        while (static::where('contractor_code', $code)->exists()) {
            $code = $originalCode . $counter;
            $counter++;
            if ($counter > 100) break;
        }
        
        return $code;
    }

    // === СУЩЕСТВУЮЩИЕ СВЯЗИ (без изменений) ===
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function executors()
    {
        return $this->hasMany(User::class, 'contractor_id')
                    ->whereHas('roles', function($q) {
                        $q->where('name', 'executor');
                    });
    }

    public function workRequests()
    {
        return $this->hasMany(WorkRequest::class, 'contractor_id');
    }

    public function anonymousShifts()
    {
        return $this->hasMany(Shift::class)->whereNull('user_id');
    }

    public function allShifts()
    {
        return Shift::where('contractor_id', $this->id)
                   ->orWhereHas('user', function($q) {
                       $q->where('contractor_id', $this->id);
                   });
    }

    public function contractorRates()
    {
        return $this->hasMany(ContractorRate::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function taxStatus()
    {
        return $this->belongsTo(TaxStatus::class);
    }

    // === СУЩЕСТВУЮЩИЕ МЕТОДЫ (без изменений) ===
    public function getTotalExecutorsCount()
    {
        return $this->executors()->count();
    }

    public function getActiveShiftsCount()
    {
        return $this->allShifts()->where('status', 'active')->count();
    }

    public function getCompletedShiftsThisMonth()
    {
        return $this->allShifts()
                   ->where('status', 'completed')
                   ->where('work_date', '>=', now()->startOfMonth())
                   ->count();
    }

    public function hasCategory($categoryId)
    {
        return $this->contractorRates()
            ->whereHas('specialty', function($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->where('is_active', true)
            ->exists();
    }
}
