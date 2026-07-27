<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Writes immutable audit trail entries for sensitive actions (admin actions,
 * money movements, webhook processing, risk decisions). Never throws, auditing
 * must not break the primary operation.
 *
 * Each row is hash-chained: `hash` is an HMAC over the row's own fields plus
 * the previous row's `hash`. Editing or deleting a row after the fact breaks
 * the chain from that point on, so verifyChain() can detect it. This is
 * tamper *evidence*, not tamper *prevention* — see the migration that added
 * these columns for the full caveat (a DB superuser could still rewrite the
 * whole chain, since the HMAC key lives in the same app's config).
 */
class AuditLogger
{
    public function log(string $action, ?string $description = null, ?Model $subject = null, array $properties = [], ?int $actorId = null): void
    {
        try {
            DB::transaction(function () use ($action, $description, $subject, $properties, $actorId) {
                $userId = $actorId ?? Auth::id();
                $auditableType = $subject?->getMorphClass();
                $auditableId = $subject?->getKey();
                $props = $properties ?: null;
                $ip = request()?->ip();
                $userAgent = substr((string) request()?->userAgent(), 0, 255);

                $prevHash = AuditLog::query()->orderByDesc('id')->value('hash');
                $hash = $this->computeHash($userId, $action, $description, $auditableType, $auditableId, $props, $ip, $userAgent, $prevHash);

                AuditLog::create([
                    'user_id' => $userId,
                    'action' => $action,
                    'description' => $description,
                    'auditable_type' => $auditableType,
                    'auditable_id' => $auditableId,
                    'properties' => $props,
                    'ip' => $ip,
                    'user_agent' => $userAgent,
                    'prev_hash' => $prevHash,
                    'hash' => $hash,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Walk the whole chain in id order and confirm every row's hash still
     * matches its recorded content and its prev_hash still matches the row
     * before it. Rows written before this feature shipped have a null hash;
     * the chain is treated as restarting (not "broken") at the first hashed
     * row after them, since those older rows were never covered by a hash.
     */
    public function verifyChain(): array
    {
        $prevHash = null;
        $checked = 0;
        $brokenAt = null;

        AuditLog::query()->orderBy('id')->chunk(500, function ($rows) use (&$prevHash, &$checked, &$brokenAt) {
            foreach ($rows as $row) {
                $checked++;

                if (is_null($row->hash)) {
                    $prevHash = null;

                    continue;
                }

                $expected = $this->computeHash(
                    $row->user_id, $row->action, $row->description, $row->auditable_type,
                    $row->auditable_id, $row->properties, $row->ip, $row->user_agent, $prevHash,
                );

                if ($row->prev_hash !== $prevHash || ! hash_equals($expected, $row->hash)) {
                    $brokenAt ??= $row->id;
                }

                $prevHash = $row->hash;
            }
        });

        return [
            'valid' => is_null($brokenAt),
            'broken_at' => $brokenAt,
            'checked' => $checked,
        ];
    }

    private function computeHash($userId, string $action, ?string $description, ?string $auditableType, $auditableId, ?array $properties, ?string $ip, ?string $userAgent, ?string $prevHash): string
    {
        $canonical = json_encode([
            'user_id' => $userId !== null ? (string) $userId : null,
            'action' => $action,
            'description' => $description,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId !== null ? (string) $auditableId : null,
            'properties' => $properties,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'prev_hash' => $prevHash,
        ]);

        return hash_hmac('sha256', $canonical, config('app.key'));
    }
}
