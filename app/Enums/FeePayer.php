<?php

namespace App\Enums;

enum FeePayer: string
{
    case Customer = 'customer';
    case Merchant = 'merchant';
    case Agent = 'agent';
    case Platform = 'platform';
    case Shared = 'shared';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Merchant => 'Merchant',
            self::Agent => 'Agent',
            self::Platform => 'Platform',
            self::Shared => 'Shared',
        };
    }
}
