<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Low Priority',
            self::MEDIUM => 'Medium Priority',
            self::HIGH => 'High Priority',
            self::URGENT => 'Urgent',
        };
    }

    public function numericValue(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => '#10B981',      // green
            self::MEDIUM => '#F59E0B',   // yellow
            self::HIGH => '#EF4444',     // red
            self::URGENT => '#DC2626',   // dark red
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
