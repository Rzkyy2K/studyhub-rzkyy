<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'day',
        'start_time',
        'end_time',
        'room',
        'session',
    ];

    protected $appends = [
        'day_label',
        'formatted_time_range',
        'is_active_now',
    ];

    public static array $dayMap = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function getDayLabelAttribute(): string
    {
        return self::$dayMap[$this->day] ?? ucfirst($this->day);
    }

    public function getFormattedTimeRangeAttribute(): string
    {
        $start = substr($this->start_time, 0, 5);
        $end = substr($this->end_time, 0, 5);
        return "{$start} – {$end}";
    }

    public function getIsActiveNowAttribute(): bool
    {
        $now = Carbon::now();
        $currentDay = strtolower($now->format('l'));

        if ($this->day !== $currentDay) {
            return false;
        }

        $currentTime = $now->format('H:i:s');
        return $currentTime >= $this->start_time && $currentTime <= $this->end_time;
    }
}
