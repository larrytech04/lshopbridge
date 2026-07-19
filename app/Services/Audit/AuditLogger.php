<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Writes immutable audit trail entries for sensitive actions (admin actions,
 * money movements, webhook processing, risk decisions). Never throws, auditing
 * must not break the primary operation.
 */
class AuditLogger
{
    public function log(string $action, ?string $description = null, ?Model $subject = null, array $properties = [], ?int $actorId = null): void
    {
        try {
            AuditLog::create([
                'user_id' => $actorId ?? Auth::id(),
                'action' => $action,
                'description' => $description,
                'auditable_type' => $subject?->getMorphClass(),
                'auditable_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'ip' => request()?->ip(),
                'user_agent' => substr((string) request()?->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
