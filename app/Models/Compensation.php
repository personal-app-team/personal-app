<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\CausesActivity;

class Compensation extends Model
{
    use LogsActivity, CausesActivity;

    protected $table = 'compensations';
    
    protected $fillable = [
        'description',
        'requested_amount',
        'approved_amount',
        'status',
        'approved_by',
        'approval_notes',
        'approved_at',
        'compensatable_id',
        'compensatable_type',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // === ЛОГИРОВАНИЕ ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'description',
                'requested_amount',
                'approved_amount',
                'status',
                'approved_by',
                'approval_notes',
                'approved_at',
                'compensatable_id',
                'compensatable_type',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Компенсация создана',
                    'updated' => 'Компенсация изменена',
                    'deleted' => 'Компенсация удалена',
                    'restored' => 'Компенсация восстановлена',
                    default => "Компенсация {$eventName}",
                };
            })
            ->useLogName('compensations')
            ->logFillable()
            ->submitEmptyLogs(false);
    }
    
    /**
     * Дополнительные настройки для лучшего отображения в логах
     */
    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'requested_amount_formatted' => $this->requested_amount ? number_format($this->requested_amount, 2) . ' ₽' : '0 ₽',
            'approved_amount_formatted' => $this->approved_amount ? number_format($this->approved_amount, 2) . ' ₽' : '0 ₽',
            'approved_by_name' => $this->approvedByUser?->full_name,
            'status_display' => $this->status_display,
            'financial_operation' => true,
            'amount_change' => $this->requested_amount != $this->approved_amount ? 'Сумма изменена при утверждении' : 'Сумма не изменена',
        ]);
    }

    // === СВЯЗИ ===
    
    public function compensatable()
    {
        return $this->morphTo();
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // === ВИРТУАЛЬНЫЕ АТРИБУТЫ ===
    
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Ожидает утверждения',
            'approved' => 'Утверждена',
            'rejected' => 'Отклонена',
            'paid' => 'Выплачена',
            default => $this->status,
        };
    }

    public function getAmountDifferenceAttribute()
    {
        if (!$this->requested_amount || !$this->approved_amount) {
            return 0;
        }
        return $this->approved_amount - $this->requested_amount;
    }

    public function getAmountDifferenceFormattedAttribute(): string
    {
        $diff = $this->amount_difference;
        if ($diff > 0) {
            return '+' . number_format($diff, 2) . ' ₽';
        } elseif ($diff < 0) {
            return number_format($diff, 2) . ' ₽';
        }
        return '0 ₽';
    }

    // === SCOPES ===
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForCompensatable($query, $compensatableType, $compensatableId)
    {
        return $query->where('compensatable_type', $compensatableType)
                    ->where('compensatable_id', $compensatableId);
    }

    // === БИЗНЕС-МЕТОДЫ ===
    
    public function approve($amount = null, $notes = null, $approvedBy = null)
    {
        return $this->update([
            'status' => 'approved',
            'approved_amount' => $amount ?? $this->requested_amount,
            'approval_notes' => $notes,
            'approved_by' => $approvedBy ?? auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function reject($reason = null, $rejectedBy = null)
    {
        return $this->update([
            'status' => 'rejected',
            'approval_notes' => $reason,
            'approved_by' => $rejectedBy ?? auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid()
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function getFinalAmountAttribute()
    {
        return $this->approved_amount ?? $this->requested_amount;
    }
}
