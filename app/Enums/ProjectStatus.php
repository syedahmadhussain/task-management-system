<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case ON_HOLD = 'on_hold';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::ON_HOLD => 'On Hold',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => '#10B981',    // green
            self::COMPLETED => '#3B82F6', // blue
            self::ON_HOLD => '#F59E0B',   // yellow
            self::CANCELLED => '#EF4444', // red
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
