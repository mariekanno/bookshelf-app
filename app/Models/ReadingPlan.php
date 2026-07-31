<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'target_date',
        'completed_at',
        'status',
    ];

    protected $casts = [
        'target_date' => 'date',
        'completed_at' => 'datetime',
        'status' => ReadingPlanStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where(
            'status',
            ReadingPlanStatus::InProgress
        );
    }

    public function scopeDueOn(Builder $query, string $date): Builder
    {
        return $query->whereDate('target_date', $date);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->inProgress()
            ->whereDate('target_date', '<', today());
    }
}
