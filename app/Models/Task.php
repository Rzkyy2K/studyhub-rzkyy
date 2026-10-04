<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'title',
        'description',
        'deadline',
        'priority',
        'status',
        'completed_at',
        'session',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'urgency',
        'urgency_label',
        'formatted_deadline',
        'formatted_time',
        'deadline_relative',
        'formatted_completed_at',
        'days_until_reset',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function getFormattedDeadlineAttribute(): string
    {
        if (!$this->deadline) {
            return '';
        }
        return $this->deadline->translatedFormat('j F Y, H:i');
    }

    public function getFormattedTimeAttribute(): string
    {
        if (!$this->deadline) {
            return '';
        }
        return $this->deadline->format('H:i');
    }

    public function getFormattedCompletedAtAttribute(): string
    {
        if (!$this->completed_at) {
            return '';
        }
        return $this->completed_at->translatedFormat('j M Y, H:i');
    }

    public function getDaysUntilResetAttribute(): ?string
    {
        if (!$this->completed_at) {
            return null;
        }

        $resetAt = $this->completed_at->copy()->addDays(7);
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($resetAt)) {
            return 'Hari ini';
        }

        $diffDays = (int) floor($now->diffInDays($resetAt, false));
        if ($diffDays >= 1) {
            return "{$diffDays} hari lagi";
        }

        $diffHours = (int) max(1, $now->diffInHours($resetAt, false));
        if ($diffHours >= 1) {
            return "{$diffHours} jam lagi";
        }

        return 'Hari ini';
    }

    public function getUrgencyAttribute(): string
    {
        if ($this->status === 'completed') {
            return 'completed';
        }

        $now = Carbon::now();
        if ($this->deadline->isPast()) {
            return 'overdue';
        }

        $diffHoursRaw = (int) ceil($now->diffInHours($this->deadline, false));
        if ($diffHoursRaw <= 12) {
            return 'danger';
        }

        if ($this->deadline->isToday() || $this->deadline->isTomorrow()) {
            return 'warning';
        }

        return 'normal';
    }

    public function getUrgencyLabelAttribute(): string
    {
        if ($this->status === 'completed') {
            return 'Selesai';
        }

        $now = Carbon::now();
        if ($this->deadline->isPast()) {
            return 'Terlambat';
        }

        $diffMinutes = (int) max(1, round($now->diffInMinutes($this->deadline, false)));
        if ($diffMinutes < 60) {
            return "Deadline tinggal {$diffMinutes} menit";
        }

        $diffHours = (int) max(1, round($now->diffInHours($this->deadline, false)));
        if ($diffHours <= 12) {
            return "Deadline tinggal {$diffHours} jam";
        }

        if ($this->deadline->isToday()) {
            return 'Hari ini, ' . $this->deadline->format('H:i');
        }

        if ($this->deadline->isTomorrow()) {
            return 'Besok, ' . $this->deadline->format('H:i');
        }

        return $this->deadline->translatedFormat('j M Y, H:i');
    }

    public function getDeadlineRelativeAttribute(): string
    {
        $now = Carbon::now();
        if ($this->deadline->isPast()) {
            return 'Terlambat';
        }
        if ($this->deadline->isToday()) {
            return 'Hari ini, ' . $this->deadline->format('H:i');
        }
        if ($this->deadline->isTomorrow()) {
            return 'Besok, ' . $this->deadline->format('H:i');
        }
        return $this->deadline->translatedFormat('j M Y, H:i');
    }
}
