<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\CausesActivity;

class ShiftExpense extends Model
{
    use HasFactory;
    use LogsActivity, CausesActivity;

    // === ТИПЫ РАСХОДОВ (основные) ===
    const TYPE_TAXI = 'taxi';
    const TYPE_MATERIALS = 'materials';
    const TYPE_FOOD = 'food';
    const TYPE_ACCOMMODATION = 'accommodation';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'shift_id',
        'type', 
        'amount',
        'receipt_photo',
        'description',
        'custom_type', // Для пользовательских типов
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // === ЛОГИРОВАНИЕ ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'shift_id',
                'type',
                'custom_type',
                'amount',
                'receipt_photo',
                'description',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Расход смены создан',
                    'updated' => 'Расход смены изменен',
                    'deleted' => 'Расход смены удален',
                    'restored' => 'Расход смены восстановлен',
                    default => "Расход смены {$eventName}",
                };
            })
            ->useLogName('shift_expenses')
            ->logFillable()
            ->submitEmptyLogs(false);
    }
    
    /**
     * Дополнительные настройки для лучшего отображения в логах
     */
    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'amount_formatted' => $this->amount ? number_format($this->amount, 2) . ' ₽' : '0 ₽',
            'type_display' => $this->type_display,
            'shift_info' => $this->shift ? "Смена #{$this->shift->id} от " . $this->shift->work_date->format('d.m.Y') : 'Смена не указана',
            'has_receipt' => !empty($this->receipt_photo) ? 'Есть чек' : 'Чека нет',
            'financial_operation' => true,
        ]);
    }

    // === СВЯЗИ ===
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    // === ВИРТУАЛЬНЫЕ АТРИБУТЫ И МЕТОДЫ ДЛЯ РАБОТЫ С ТИПАМИ ===
    
    /**
     * Получить отображаемое название типа
     */
    public function getTypeDisplayAttribute(): string
    {
        // Если есть пользовательский тип - используем его
        if ($this->type === 'custom' && $this->custom_type) {
            return $this->custom_type;
        }
        
        // Иначе используем стандартные типы
        return match($this->type) {
            self::TYPE_TAXI => '🚕 Такси',
            self::TYPE_MATERIALS => '🛠️ Материалы',
            self::TYPE_FOOD => '🍔 Питание',
            self::TYPE_ACCOMMODATION => '🏨 Проживание',
            self::TYPE_OTHER => '📄 Прочие расходы',
            'custom' => $this->custom_type ?? 'Пользовательский',
            default => $this->type,
        };
    }

    /**
     * Получить все доступные типы (стандартные + возможность кастомных)
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_TAXI => 'Такси',
            self::TYPE_MATERIALS => 'Материалы',
            self::TYPE_FOOD => 'Питание',
            self::TYPE_ACCOMMODATION => 'Проживание',
            self::TYPE_OTHER => 'Прочие расходы',
            'custom' => 'Пользовательский тип',
        ];
    }

    /**
     * Получить тип для сохранения (обработка пользовательских типов)
     */
    public function setTypeAttribute($value)
    {
        // Если передан массив с custom, сохраняем отдельно
        if (is_array($value) && isset($value['type']) && $value['type'] === 'custom') {
            $this->attributes['type'] = 'custom';
            $this->attributes['custom_type'] = $value['custom_type'] ?? null;
        } else {
            $this->attributes['type'] = $value;
        }
    }

    /**
     * Проверить, является ли тип пользовательским
     */
    public function isCustomType(): bool
    {
        return $this->type === 'custom';
    }

    /**
     * Создать пользовательский тип расхода
     */
    public static function createCustomType(string $name, string $description = null): void
    {
        // Логика для добавления пользовательских типов в систему
        // Можно сохранять в кэш, конфиг или отдельную таблицу
        \Cache::remember('shift_expense_custom_types', 3600, function () use ($name, $description) {
            $types = \Cache::get('shift_expense_custom_types', []);
            $types[$name] = $description ?? $name;
            return $types;
        });
    }

    /**
     * Получить все пользовательские типы
     */
    public static function getCustomTypes(): array
    {
        return \Cache::get('shift_expense_custom_types', []);
    }

    /**
     * Получить все типы (стандартные + пользовательские)
     */
    public static function getAllTypes(): array
    {
        $standard = self::getTypeOptions();
        $custom = self::getCustomTypes();
        
        // Форматируем пользовательские типы для отображения
        $formattedCustom = [];
        foreach ($custom as $key => $value) {
            $formattedCustom["custom:{$key}"] = "📝 {$value} (пользовательский)";
        }
        
        return $standard + $formattedCustom;
    }

    // === SCOPES ===
    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopeWithReceipt($query)
    {
        return $query->whereNotNull('receipt_photo');
    }

    public function scopeWithoutReceipt($query)
    {
        return $query->whereNull('receipt_photo');
    }

    public function scopeTaxi($query)
    {
        return $query->where('type', self::TYPE_TAXI);
    }

    public function scopeMaterials($query)
    {
        return $query->where('type', self::TYPE_MATERIALS);
    }

    public function scopeFood($query)
    {
        return $query->where('type', self::TYPE_FOOD);
    }

    public function scopeAccommodation($query)
    {
        return $query->where('type', self::TYPE_ACCOMMODATION);
    }

    public function scopeOther($query)
    {
        return $query->where('type', self::TYPE_OTHER);
    }

    public function scopeCustom($query)
    {
        return $query->where('type', 'custom');
    }

    // === БИЗНЕС-МЕТОДЫ ===
    
    public function isTaxi(): bool
    {
        return $this->type === self::TYPE_TAXI;
    }

    public function isMaterials(): bool
    {
        return $this->type === self::TYPE_MATERIALS;
    }

    public function isFood(): bool
    {
        return $this->type === self::TYPE_FOOD;
    }

    public function isAccommodation(): bool
    {
        return $this->type === self::TYPE_ACCOMMODATION;
    }

    public function isOther(): bool
    {
        return $this->type === self::TYPE_OTHER;
    }

    public function getEffectiveType(): string
    {
        return $this->isCustomType() ? $this->custom_type : $this->type;
    }
}
