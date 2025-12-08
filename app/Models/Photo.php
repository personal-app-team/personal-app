<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Photo extends Model
{
    use HasFactory, LogsActivity;

    // Типы фотографий
    const TYPE_SHIFT = 'shift';
    const TYPE_LOCATION = 'location';
    const TYPE_EXPENSE = 'expense';
    const TYPE_MASS_REPORT = 'mass_report';
    const TYPE_WORKER = 'worker';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'photoable_id',
        'photoable_type',
        'file_path',
        'file_name',
        'original_name',
        'mime_type',
        'file_size',
        'description',
        'taken_at',
        'latitude',
        'longitude',
        'photo_type',
        'is_verified',
        'verified_by_id',
        'verified_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'file_size' => 'integer',
    ];

    // === ЛОГИРОВАНИЕ ===
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'photoable_id', 'photoable_type', 'file_path', 'file_name', 
                'original_name', 'mime_type', 'file_size', 'description', 
                'taken_at', 'latitude', 'longitude', 'photo_type', 'is_verified'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function(string $eventName) {
                $type = $this->getPhotoTypeDisplay();
                return match($eventName) {
                    'created' => "{$type} фотография создана",
                    'updated' => "{$type} фотография изменена",
                    'deleted' => "{$type} фотография удалена",
                    'restored' => "{$type} фотография восстановлена",
                    default => "Фотография {$eventName}",
                };
            })
            ->useLogName('photos')
            ->logFillable()
            ->submitEmptyLogs(false);
    }

    public function tapActivity(\Spatie\Activitylog\Models\Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'photoable_info' => $this->photoable_info,
            'file_size_formatted' => $this->file_size_formatted,
            'has_coordinates' => !empty($this->latitude) && !empty($this->longitude),
            'is_verified' => $this->is_verified,
            'photo_type_display' => $this->getPhotoTypeDisplay(),
        ]);
    }

    // === ПОЛИМОРФНАЯ СВЯЗЬ ===
    public function photoable()
    {
        return $this->morphTo();
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    // === АТРИБУТЫ ===
    public function getUrlAttribute()
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');
        return $disk->url($this->file_path);
    }

    public function getPhotoableInfoAttribute(): string
    {
        if (!$this->photoable) {
            return 'Не указано';
        }
        
        return match($this->photoable_type) {
            'App\\Models\\Shift' => "Смена #{$this->photoable_id}",
            'App\\Models\\VisitedLocation' => "Локация #{$this->photoable_id}",
            'App\\Models\\MassPersonnelReport' => "Отчет #{$this->photoable_id}",
            'App\\Models\\Expense' => "Расход #{$this->photoable_id}",
            'App\\Models\\ContractorWorker' => "Работник #{$this->photoable_id}",
            default => "{$this->photoable_type} #{$this->photoable_id}",
        };
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if ($this->file_size < 1024) {
            return $this->file_size . ' B';
        } elseif ($this->file_size < 1048576) {
            return round($this->file_size / 1024, 2) . ' KB';
        } else {
            return round($this->file_size / 1048576, 2) . ' MB';
        }
    }

    public function getPhotoTypeDisplay(): string
    {
        return match($this->photo_type) {
            self::TYPE_SHIFT => 'Смены',
            self::TYPE_LOCATION => 'Локации',
            self::TYPE_EXPENSE => 'Чека расхода',
            self::TYPE_MASS_REPORT => 'Массового отчета',
            self::TYPE_WORKER => 'Работника',
            self::TYPE_OTHER => 'Другая',
            default => 'Неизвестный тип',
        };
    }

    public static function getPhotoTypeOptions(): array
    {
        return [
            self::TYPE_SHIFT => '📸 Смена',
            self::TYPE_LOCATION => '📍 Локация',
            self::TYPE_EXPENSE => '🧾 Чек расхода',
            self::TYPE_MASS_REPORT => '👥 Массовый отчет',
            self::TYPE_WORKER => '👷 Работник',
            self::TYPE_OTHER => '📷 Другое',
        ];
    }

    // === SCOPES ===
    public function scopeShift($query)
    {
        return $query->where('photo_type', self::TYPE_SHIFT);
    }

    public function scopeLocation($query)
    {
        return $query->where('photo_type', self::TYPE_LOCATION);
    }

    public function scopeExpense($query)
    {
        return $query->where('photo_type', self::TYPE_EXPENSE);
    }

    public function scopeMassReport($query)
    {
        return $query->where('photo_type', self::TYPE_MASS_REPORT);
    }

    public function scopeWorker($query)
    {
        return $query->where('photo_type', self::TYPE_WORKER);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
                    ->whereNotNull('longitude');
    }

    public function scopeWithoutCoordinates($query)
    {
        return $query->whereNull('latitude')
                    ->orWhereNull('longitude');
    }

    // === МЕТОДЫ ===
    public function verify(User $user)
    {
        $this->update([
            'is_verified' => true,
            'verified_by_id' => $user->id,
            'verified_at' => now(),
        ]);
    }

    public function unverify()
    {
        $this->update([
            'is_verified' => false,
            'verified_by_id' => null,
            'verified_at' => null,
        ]);
    }
}
