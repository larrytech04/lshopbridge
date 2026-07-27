<?php

namespace App\Enums;

enum GuideFeedbackReason: string
{
    case Outdated = 'outdated';
    case Unclear = 'unclear';
    case MissingSteps = 'missing_steps';
    case BrokenLink = 'broken_link';
    case OutdatedScreenshot = 'outdated_screenshot';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Outdated => 'Information is outdated',
            self::Unclear => 'Instructions are unclear',
            self::MissingSteps => 'Missing steps',
            self::BrokenLink => 'Link does not work',
            self::OutdatedScreenshot => 'Screenshot is outdated',
            self::Other => 'Other',
        };
    }
}
