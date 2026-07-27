<?php

namespace App\Services\Admin;

use App\Models\Guide;
use App\Models\GuideFeedback;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Str;

class GuideAdminService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(array $data, User $admin): Guide
    {
        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        $guide = Guide::create($data + ['updated_by' => $admin->id]);
        $this->audit->log('admin.guide.created', "Created guide {$guide->title}", $guide, [], $admin->id);

        return $guide;
    }

    public function update(Guide $guide, array $data, User $admin): Guide
    {
        // The slug is a stable identifier for a public/indexed URL — never
        // let an update silently rename it.
        unset($data['slug']);

        $before = $guide->only(['title', 'category', 'is_published']);
        $guide->update($data + ['updated_by' => $admin->id]);
        $this->audit->log('admin.guide.updated', "Updated guide {$guide->title}", $guide, ['before' => $before], $admin->id);

        return $guide->fresh();
    }

    /** Archive-not-delete: soft-deletes so guides linked from historical support conversations aren't dead links. */
    public function archive(Guide $guide, User $admin): void
    {
        $guide->update(['is_published' => false, 'updated_by' => $admin->id]);
        $this->audit->log('admin.guide.archived', "Archived guide {$guide->title}", $guide, [], $admin->id);
        $guide->delete();
    }

    public function restore(Guide $guide, User $admin): Guide
    {
        $guide->restore();
        $guide->update(['updated_by' => $admin->id]);
        $this->audit->log('admin.guide.restored', "Restored guide {$guide->title}", $guide, [], $admin->id);

        return $guide->fresh();
    }

    public function recordFeedback(Guide $guide, bool $wasHelpful, ?string $reason, ?string $comment, ?User $user, ?string $ip): GuideFeedback
    {
        return $guide->feedback()->create([
            'user_id' => $user?->id,
            'was_helpful' => $wasHelpful,
            'reason' => $wasHelpful ? null : $reason,
            'comment' => $comment,
            'ip' => $ip,
            'created_at' => now(),
        ]);
    }

    /** @return array{helpful:int, not_helpful:int, reasons:array<string,int>} */
    public function feedbackSummary(Guide $guide): array
    {
        $feedback = $guide->feedback;

        return [
            'helpful' => $feedback->where('was_helpful', true)->count(),
            'not_helpful' => $feedback->where('was_helpful', false)->count(),
            'reasons' => $feedback->where('was_helpful', false)
                ->groupBy(fn ($f) => $f->reason?->value ?? 'other')
                ->map->count()
                ->all(),
        ];
    }
}
