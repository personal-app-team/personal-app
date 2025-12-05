<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\CausesActivity;
use Illuminate\Support\Facades\Cache;

class Expense extends Model
{
    use HasFactory, LogsActivity, CausesActivity;

    // === ТИПЫ РАСХОДОВ ===
    const TYPE_TAXI = 'taxi';
    const TYPE_MATERIALS = 'materials';
    const TYPE_FOOD = 'food';
    const TYPE_ACCOMMODATION = 'accommodation';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'expensable_id',
        'expensable_type',
        'type',
        'amount',
        'receipt_photo',
        'description',
        'custom_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['expensable_id', 'expensable_type', 'type', 'custom_type', 'amount', 'receipt_photo', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Расход создан',
                    'updated' => 'Расход изменен',
                    'deleted' => 'Расход удален',
                    'restored' => 'Расход восстановлен',
                    default => "Расход {$eventName}",
                };
            })
            ->useLogName('expenses')
            ->logFillable()
            ->submitEmptyLogs(false);
    }
    
    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'amount_formatted' => number_format($this->amount, 2) . ' ₽',
            'type_display' => $this->type_display,
            'expensable_info' => $this->expensable_info,
            'has_receipt' => !empty($this->receipt_photo) ? 'Есть чек' : 'Чека нет',
            'financial_operation' => true,
        ]);
    }

    public function expensable()
    {
        return $this->morphTo();
    }
    
    public function getExpensableInfoAttribute(): string
    {
        if (!$this->expensable) {
            return 'Не указано';
        }
        
        return match($this->expensable_type) {
            'App\\Models\\Shift' => "Смена #{$this->expensable_id}",
            'App\\Models\\MassPersonnelReport' => "Отчет по массовому персоналу #{$this->expensable_id}",
            default => "{$this->expensable_type} #{$this->expensable_id}",
        };
    }

    public function getTypeDisplayAttribute(): string
    {
        if ($this->type === 'custom' && $this->custom_type) {
            return $this->custom_type;
        }
        
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

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_TAXI => 'Такси',
            self::TYPE_MATERIALS => 'Материалы',
            self::TYPE_FOOD => 'Питание',
            self::TYPE_ACCOMMODATION => 'Проживание',
            self::TYPE_OTHER => 'Прочие расходы',
            'custom' => '📝 Пользовательский тип',
        ];
    }

    public function isCustomType(): bool
    {
        return $this->type === 'custom';
    }

    public static function getCustomTypes(): array
    {
        return Cache::get('expense_custom_types', []);
    }

    public static function getAllTypes(): array
    {
        $standard = self::getTypeOptions();
        $custom = self::getCustomTypes();
        
        $formattedCustom = [];
        foreach ($custom as $key => $value) {
            $formattedCustom["custom:{$key}"] = "📝 {$value} (пользовательский)";
        }
        
        return $standard + $formattedCustom;
    }

    public static function createCustomType(string $name, string $description = null): void
    {
        $types = Cache::get('expense_custom_types', []);
        $types[$name] = $description ?? $name;
        Cache::put('expense_custom_types', $types, now()->addMonth());
    }

    public function scopeForExpensable($query, $expensableType, $expensableId)
    {
        return $query->where('expensable_type', $expensableType)
                    ->where('expensable_id', $expensableId);
    }

    public function scopeForShift($query, $shiftId)
    {
        return $query->forExpensable('App\\Models\\Shift', $shiftId);
    }

    public function scopeForMassPersonnelReport($query, $reportId)
    {
        return $query->forExpensable('App\\Models\\MassPersonnelReport', $reportId);
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

    public function scopeWithReceipt($query)
    {
        return $query->whereNotNull('receipt_photo');
    }

    public function scopeWithoutReceipt($query)
    {
        return $query->whereNull('receipt_photo');
    }
}
