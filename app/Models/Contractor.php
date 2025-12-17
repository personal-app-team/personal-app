<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Contractor extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'contractor_code',
        'inn',
        'address',
        'bank_details',
        'director',
        'director_phone',
        'director_email',
        'company_phone',
        'company_email',
        'contract_type_id',
        'tax_status_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contractor) {
            if (empty($contractor->contractor_code)) {
                $contractor->contractor_code = static::generateContractorCode($contractor->name);
            }
        });

        static::updating(function ($contractor) {
            if ($contractor->isDirty('name') && !$contractor->isDirty('contractor_code')) {
                $contractor->contractor_code = static::generateContractorCode($contractor->name);
            }
        });
    }

    // === МЕТОД ДЛЯ ACTIVITYLOG ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'contractor_code',
                'director',
                'director_phone',
                'director_email',
                'company_phone',
                'company_email',
                'contract_type_id',
                'tax_status_id',
                'inn',
                'address',
                'bank_details',
                'is_active',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => '🏢 Подрядчик создан',
                'updated' => '✏️ Подрядчик обновлен',
                'deleted' => '🗑️ Подрядчик удален',
                'restored' => '♻️ Подрядчик восстановлен',
                default => "🏢 Подрядчик был {$eventName}",
            })
            ->useLogName('contractors');
    }

    // === ГЕНЕРАЦИЯ КОДА ПОДРЯДЧИКА ===
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
        ];
        
        $transliterated = strtr(mb_strtolower($name, 'UTF-8'), $transliterationMap);
        $words = array_filter(explode(' ', preg_replace('/[^a-zA-Z0-9\s]/u', '', $transliterated)));
        
        $code = '';
        foreach ($words as $word) {
            $cleanWord = trim($word);
            if (!empty($cleanWord) && !in_array(mb_strtolower($cleanWord), $ignoreWords)) {
                $code .= strtoupper(substr($cleanWord, 0, 1));
                if (strlen($code) >= 3) break;
            }
        }
        
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

    // === СВЯЗИ ===
    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function taxStatus()
    {
        return $this->belongsTo(TaxStatus::class);
    }

    public function contractorRates()
    {
        return $this->hasMany(ContractorRate::class);
    }

    public function workRequests()
    {
        return $this->hasMany(WorkRequest::class, 'contractor_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // === ВЫЧИСЛЯЕМЫЕ ПОЛЯ ===
    
    /**
     * Специализации (категории, в которых есть активные ставки)
     */
    public function getSpecializationsAttribute()
    {
        return $this->contractorRates()
            ->where('is_active', true)
            ->with('category')
            ->get()
            ->pluck('category.name')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Получить всех пользователей-представителей подрядчика
     * (users с ролью contractor_* и contractor_id = текущий подрядчик)
     */
    public function representativeUsers()
    {
        return User::where('contractor_id', $this->id)
            ->whereHas('roles', function($q) {
                $q->where('name', 'like', 'contractor_%');
            })
            ->get();
    }

    /**
     * Получить активные категории подрядчика
     */
    public function activeCategories()
    {
        return Category::whereHas('contractorRates', function($q) {
            $q->where('contractor_id', $this->id)
              ->where('is_active', true);
        })->get();
    }

    /**
     * Получить все смены подрядчика (через заявки и напрямую)
     */
    public function allShifts()
    {
        return Shift::whereHas('workRequest', function($q) {
                $q->where('contractor_id', $this->id);
            })
            ->orWhere('contractor_id', $this->id);
    }

    /**
     * Проверить, есть ли у подрядчика активные ставки для категории
     */
    public function hasCategory($categoryId)
    {
        return $this->contractorRates()
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->exists();
    }
}
