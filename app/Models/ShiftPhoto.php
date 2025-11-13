<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage; // Import the Storage facade

class ShiftPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'visited_location_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'description',
        'taken_at',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function visitedLocation()
    {
        return $this->belongsTo(VisitedLocation::class);
    }

    public function getUrlAttribute()
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');
        return $disk->url($this->file_path);
    }
}
