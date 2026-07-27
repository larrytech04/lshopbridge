<?php

namespace App\Enums;

enum ImportRunStatus: string
{
    case Preparing = 'preparing';
    case Reading = 'reading_source';
    case Validating = 'validating';
    case DownloadingMedia = 'downloading_media';
    case CreatingCategories = 'creating_categories';
    case CreatingProducts = 'creating_products';
    case CreatingVariants = 'creating_variants';
    case SynchronizingInventory = 'synchronizing_inventory';
    case Completed = 'completed';
    case CompletedWithWarnings = 'completed_with_warnings';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Preparing => 'Preparing',
            self::Reading => 'Reading source',
            self::Validating => 'Validating',
            self::DownloadingMedia => 'Downloading media',
            self::CreatingCategories => 'Creating categories',
            self::CreatingProducts => 'Creating products',
            self::CreatingVariants => 'Creating variants',
            self::SynchronizingInventory => 'Synchronizing inventory',
            self::Completed => 'Completed',
            self::CompletedWithWarnings => 'Completed with warnings',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-400/30',
            self::CompletedWithWarnings => 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-400/30',
            self::Failed => 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-400/30',
            default => 'bg-sky-500/15 text-sky-600 ring-1 ring-sky-400/30',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::CompletedWithWarnings, self::Failed], true);
    }
}
