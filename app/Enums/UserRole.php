<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Agent = 'agent';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Agent => 'Shipping Agent',
            self::Admin => 'Administrator',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::Admin, self::SuperAdmin], true);
    }
}
