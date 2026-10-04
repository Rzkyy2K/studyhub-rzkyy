<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Todo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'priority',
        'due_date',
        'status',
        'session',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected $appends = [
        'is_completed',
        'formatted_due_date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getFormattedDueDateAttribute(): ?string
    {
        return $this->due_date ? $this->due_date->translatedFormat('j M Y') : null;
    }
}
