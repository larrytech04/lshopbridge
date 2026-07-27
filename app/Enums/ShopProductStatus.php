<?php

namespace App\Enums;

/**
 * Stored lifecycle status. "Scheduled" is never stored here — it's a computed
 * display state (status=draft + scheduled_publish_at in the future), see
 * ShopProductAdminService::computeStatus().
 */
enum ShopProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Disabled = 'disabled';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Disabled => 'Disabled',
            self::Archived => 'Archived',
        };
    }
}
