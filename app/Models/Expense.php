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

    // Имя таблицы
    protected $table = 'expenses';

    // === ТИПЫ РАСХОДОВ ===
    const TYPE_TAXI = 'taxi';
    const TYPE_MATERIALS = 'materials';
    const TYPE_FOOD = 'food';
    const TYPE_ACCOMMODATION = 'accommodation';
    const TYPE_OTHER = 'other';
    const TYPE_CUSTOM = 'custom';
    
    // Статусы расходов
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PAID = 'paid';

    protected $fillable = [
        'expensable_id',
        'expensable_type',
        'name', // название расхода (из старой таблицы shift_expenses)
        'type',
        'amount',
        'description',
        'custom_type',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'type' => self::TYPE_OTHER,
        'amount' => 0,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['expensable_id', 'expensable_type', 'name', 'type', 'custom_type', 'amount', 'description', 'status'])
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
            'status_display' => $this->status_display,
        ]);
    }

    public function photos()
    {
        return $this->morphMany(Photo::class, 'photoable');
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
        if ($this->type === self::TYPE_CUSTOM && $this->custom_type) {
            return $this->custom_type;
        }
        
        return match($this->type) {
            self::TYPE_TAXI => '🚕 Такси',
            self::TYPE_MATERIALS => '🛠️ Материалы',
            self::TYPE_FOOD => '🍔 Питание',
            self::TYPE_ACCOMMODATION => '🏨 Проживание',
            self::TYPE_OTHER => '📄 Прочие расходы',
            self::TYPE_CUSTOM => $this->custom_type ?? 'Пользовательский',
            default => $this->type,
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => '⏳ Ожидает',
            self::STATUS_APPROVED => '✅ Подтвержден',
            self::STATUS_REJECTED => '❌ Отклонен',
            self::STATUS_PAID => '💰 Оплачен',
            default => $this->status,
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
            self::TYPE_CUSTOM => '📝 Пользовательский тип',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Ожидает',
            self::STATUS_APPROVED => 'Подтвержден',
            self::STATUS_REJECTED => 'Отклонен',
            self::STATUS_PAID => 'Оплачен',
        ];
    }

    public function isCustomType(): bool
    {
        return $this->type === self::TYPE_CUSTOM;
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
        return $query->where('type', self::TYPE_CUSTOM);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    // Метод для подтверждения расхода
    public function approve($reason = null)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
        ]);
        
        activity()
            ->performedOn($this)
            ->log('Расход подтвержден' . ($reason ? ": {$reason}" : ''));
    }

    // Метод для отклонения расхода
    public function reject($reason = null)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
        ]);
        
        activity()
            ->performedOn($this)
            ->log('Расход отклонен' . ($reason ? ": {$reason}" : ''));
    }

    // Метод для отметки как оплаченный
    public function markAsPaid()
    {
        $this->update([
            'status' => self::STATUS_PAID,
        ]);
        
        activity()
            ->performedOn($this)
            ->log('Расход отмечен как оплаченный');
    }
}
