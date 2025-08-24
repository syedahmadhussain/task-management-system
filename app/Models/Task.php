<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'project_id',
        'user_id',
        'assigned_to',
        'priority',
        'status',
        'due_date',
        'estimated_time',
        'org_id',
        'completed_at',
        'completion_notes',
        'tags',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'estimated_time' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompletedInLast($query, $days = 30)
    {
        return $query->where('status', 'completed')
                    ->where('completed_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', Carbon::now()->toDateString())
                    ->where('status', '!=', 'completed');
    }

    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    public function scopeOrderByDueDate($query)
    {
        return $query->orderBy('due_date', 'asc');
    }

    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => Carbon::now(),
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && $this->status !== 'completed';
    }
}
