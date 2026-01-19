<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RespondRequest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'work_request_id',
        'user_id',
        'message',
        'status',
        'approved_at',
        'rejected_at',
        'approved_by',
        'rejection_reason'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'message', 'approved_by', 'rejection_reason'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(function(string $eventName) {
                return match($eventName) {
                    'created' => 'Отклик создан',
                    'updated' => 'Отклик изменен',
                    'deleted' => 'Отклик удален',
                    default => "Отклик {$eventName}"
                };
            })
            ->useLogName('respond_requests');
    }

    // === СВЯЗИ ===
    public function workRequest()
    {
        return $this->belongsTo(WorkRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // === МЕТОДЫ ===
    public function approve($approverId)
    {
        return $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approverId
        ]);
    }

    public function reject($approverId, $reason = null)
    {
        return $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'approved_by' => $approverId,
            'rejection_reason' => $reason
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
}
