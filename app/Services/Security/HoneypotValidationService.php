<?php

namespace App\Services\Security;

use Illuminate\Http\Request;

/**
 * Invisible honeypot field. The field name rotates daily across a small set
 * of believable names so a scraper that hardcodes one exact field name stops
 * working within a day, without needing a database-backed rotation schedule.
 * Never rely on this alone — see FormProtectionService, which only escalates
 * to "highly suspicious" when a honeypot hit is combined with another signal.
 */
class HoneypotValidationService
{
    private const CANDIDATE_NAMES = ['company_website', 'secondary_email', 'contact_url', 'fax_number'];

    /** Stable for a given calendar day, so a single visitor's retry after a validation error still matches. */
    public function fieldName(): string
    {
        $index = (int) date('z') % count(self::CANDIDATE_NAMES);

        return self::CANDIDATE_NAMES[$index];
    }

    public function triggered(Request $request): bool
    {
        foreach (self::CANDIDATE_NAMES as $name) {
            if (filled($request->input($name))) {
                return true;
            }
        }

        return false;
    }
}
