<?php

namespace App\Services\Security;

use App\Models\FormFingerprint;
use Illuminate\Support\Str;

/**
 * Content-based duplicate/replay detection. Fingerprints are computed only
 * from safe, non-sensitive fields the caller explicitly passes in — never
 * call this with a password, verification code, or card data.
 */
class FormFingerprintService
{
    private const DUPLICATE_OCCURRENCE_THRESHOLD = 5;
    private const DUPLICATE_DISTINCT_IP_THRESHOLD = 5;
    private const MAX_TRACKED_VALUES = 20;

    public function fingerprint(array $safeFields): string
    {
        $normalized = collect($safeFields)
            ->map(fn ($value) => is_string($value) ? Str::of($value)->lower()->squish()->toString() : $value)
            ->sortKeys()
            ->all();

        return hash('sha256', json_encode($normalized));
    }

    public function record(string $hash, string $formType, ?string $ipHash): FormFingerprint
    {
        $fingerprint = FormFingerprint::firstOrNew(['fingerprint_hash' => $hash]);

        if (! $fingerprint->exists) {
            $fingerprint->form_types = [];
            $fingerprint->ip_hashes = [];
            $fingerprint->occurrence_count = 0;
            $fingerprint->first_seen_at = now();
        }

        $fingerprint->form_types = array_slice(array_values(array_unique([...($fingerprint->form_types ?? []), $formType])), 0, self::MAX_TRACKED_VALUES);
        if ($ipHash) {
            $fingerprint->ip_hashes = array_slice(array_values(array_unique([...($fingerprint->ip_hashes ?? []), $ipHash])), 0, self::MAX_TRACKED_VALUES);
        }
        $fingerprint->occurrence_count = ($fingerprint->occurrence_count ?? 0) + 1;
        $fingerprint->last_seen_at = now();
        $fingerprint->save();

        return $fingerprint;
    }

    public function isSuspicious(FormFingerprint $fingerprint): bool
    {
        return $fingerprint->blocked
            || $fingerprint->occurrence_count >= self::DUPLICATE_OCCURRENCE_THRESHOLD
            || $fingerprint->distinctIpCount() >= self::DUPLICATE_DISTINCT_IP_THRESHOLD;
    }
}
