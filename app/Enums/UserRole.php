<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::MANAGER => 'Manager',
            self::MEMBER => 'Team Member',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN => ['*'],
            self::MANAGER => [
                'users.create',
                'users.update',
                'projects.*',
                'tasks.*',
                'reports.view',
            ],
            self::MEMBER => [
                'tasks.view',
                'tasks.update_own',
                'profile.update',
            ],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
