<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\CausesActivity;

class MassPersonnelReport extends Model
{
    use HasFactory, LogsActivity, CausesActivity;

    protected $fillable = [
        'request_id',
        'workers_count',
        'total_hours',
        'worker_names',
        'compensation_amount',
        'compensation_description',
        'tax_status_id',
        'contract_type_id',
        'category_id',
        'contractor_rate_id',
        'work_type_id',
        'base_hourly_rate',
        'total_amount',
        'expenses_total',
        'tax_amount',
        'net_amount',
        'status',
        'submitted_at',
        'approved_at',
        'paid_at'
    ];

    protected $casts = [
        'total_hours' => 'decimal:2',
        'compensation_amount' => 'decimal:2',
        'base_hourly_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expenses_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'request_id', 'workers_count', 'total_hours', 'compensation_amount',
                'tax_status_id', 'contract_type_id', 'category_id', 'contractor_rate_id',
                'work_type_id', 'base_hourly_rate', 'total_amount', 'expenses_total',
                'tax_amount', 'net_amount', 'status', 'submitted_at', 'approved_at', 'paid_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Отчет по массовому персоналу создан',
                    'updated' => 'Отчет по массовому персоналу изменен',
                    'deleted' => 'Отчет по массовому персоналу удален',
                    'restored' => 'Отчет по массовому персоналу восстановлен',
                    default => "Отчет {$eventName}",
                };
            })
            ->useLogName('mass_personnel_report')
            ->logFillable()
            ->submitEmptyLogs(false);
    }

    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'financial_operation' => true,
            'total_amount_formatted' => number_format($this->total_amount, 2) . ' ₽',
            'net_amount_formatted' => number_format($this->net_amount, 2) . ' ₽',
            'status_ru' => match($this->status) {
                'draft' => 'Черновик',
                'pending_approval' => 'Ожидает утверждения',
                'approved' => 'Утвержден',
                'paid' => 'Оплачен',
                default => $this->status,
            }
        ]);
    }

    public function photos()
    {
        return $this->morphMany(Photo::class, 'photoable');
    }

    public function contractorWorkers()
    {
        return $this->hasMany(ContractorWorker::class);
    }

    public function workRequest()
    {
        return $this->belongsTo(WorkRequest::class, 'request_id');
    }

    public function taxStatus()
    {
        return $this->belongsTo(TaxStatus::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function contractorRate()
    {
        return $this->belongsTo(ContractorRate::class);
    }

    public function workType()
    {
        return $this->belongsTo(WorkType::class);
    }

    public function visitedLocations()
    {
        return $this->morphMany(VisitedLocation::class, 'visitable');
    }

    public function compensations()
    {
        return $this->morphMany(Compensation::class, 'compensatable');
    }

    public function expenses()
    {
        return $this->morphMany(Expense::class, "expensable");
    }

    public function getExpensesTotalAttribute()
    {
        return $this->expenses()->sum("amount");
    }

    public function calculateTotalAmount()
    {
        // Если базовая ставка не установлена, определяем ее
        if (!$this->base_hourly_rate) {
            $this->base_hourly_rate = $this->determineBaseRate();
            $this->save();
        }
        
        $baseAmount = $this->base_hourly_rate * $this->total_hours;
        $compensation = $this->compensation_amount;
        $expenses = $this->expenses_total;

        $workersTotal = $this->contractorWorkers()
            ->confirmed()
            ->get()
            ->sum('amount');

        return $baseAmount + $compensation + $expenses + $workersTotal;
    }

    public function getTotalHoursAttribute()
    {
        if ($this->contractorWorkers()->confirmed()->exists()) {
            return $this->contractorWorkers()
                ->confirmed()
                ->get()
                ->sum('calculated_hours');
        }

        return $this->getAttribute('total_hours') ??
            $this->visitedLocations->sum('duration_minutes') / 60;
    }

    public function getWorkersCountAttribute()
    {
        return $this->contractorWorkers()->confirmed()->count();
    }

    public function getWorkerNamesAttribute()
    {
        return $this->contractorWorkers()
            ->confirmed()
            ->get()
            ->pluck('full_name')
            ->implode(', ');
    }

    public function confirmAllWorkers($userId, $reason = null)
    {
        foreach ($this->contractorWorkers()->unconfirmed()->get() as $worker) {
            $worker->confirm($userId, $reason);
        }
    }

    public function calculateTaxAmount()
    {
        $totalAmount = $this->total_amount ?: $this->calculateTotalAmount();
        $taxRate = $this->taxStatus?->tax_rate ?? 0;

        return $totalAmount * $taxRate;
    }

    public function calculateNetAmount()
    {
        $totalAmount = $this->total_amount ?: $this->calculateTotalAmount();
        $taxAmount = $this->tax_amount ?: $this->calculateTaxAmount();

        return $totalAmount - $taxAmount;
    }

    public function updateCalculations()
    {
        $this->total_amount = $this->calculateTotalAmount();
        $this->tax_amount = $this->calculateTaxAmount();
        $this->net_amount = $this->calculateNetAmount();

        $this->save();
    }

    public function getTotalTimeFromLocations()
    {
        return $this->visitedLocations()->sum('duration_minutes') / 60;
    }

    public function submitForApproval()
    {
        $this->update([
            'status' => 'pending_approval',
            'submitted_at' => now()
        ]);

        activity()
            ->performedOn($this)
            ->log('Отчет отправлен на утверждение');
    }

    public function approve()
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now()
        ]);

        activity()
            ->performedOn($this)
            ->log('Отчет утвержден');
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        activity()
            ->performedOn($this)
            ->log('Отчет отмечен как оплаченный');
    }

    public function determineBaseRate(): float
    {
        if ($this->contractor_rate_id) {
            $contractorRate = $this->contractorRate;
            if ($contractorRate && $contractorRate->is_active) {
                return (float) $contractorRate->hourly_rate;
            }
        }

        if ($this->category_id) {
            $contractorRate = ContractorRate::where('category_id', $this->category_id)
                ->where('rate_type', 'mass')
                ->where('is_active', true)
                ->first();

            if ($contractorRate) {
                $this->contractor_rate_id = $contractorRate->id;
                $this->save();
                return (float) $contractorRate->hourly_rate;
            }
        }

        return 0.0;
    }
}
