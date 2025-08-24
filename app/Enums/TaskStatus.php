<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => '#6B7280',      // gray
            self::IN_PROGRESS => '#3B82F6', // blue
            self::COMPLETED => '#10B981',   // green
            self::CANCELLED => '#EF4444',   // red
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function canTransitionTo(TaskStatus $status): bool
    {
        return match ($this) {
            self::PENDING => $status !== self::PENDING,
            self::IN_PROGRESS => in_array($status, [self::COMPLETED, self::CANCELLED]),
            self::COMPLETED => $status === self::IN_PROGRESS,
            self::CANCELLED => $status === self::PENDING,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
