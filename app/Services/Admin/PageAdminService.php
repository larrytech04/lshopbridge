<?php

namespace App\Services\Admin;

use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Never overwrites a published page's content in place: every update snapshots
 * the page's current state into page_revisions first, then applies the change
 * and bumps the version number. Nothing is silently lost.
 */
class PageAdminService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(array $data, User $admin): Page
    {
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $page = Page::create($data + ['version' => 1, 'last_reviewed_at' => now(), 'updated_by' => $admin->id]);
        $this->audit->log('admin.page.created', "Created page {$page->title}", $page, [], $admin->id);

        return $page;
    }

    public function update(Page $page, array $data, User $admin): Page
    {
        // The slug backs a real, potentially-indexed public URL — never let an
        // update silently rename it (the edit form disables the field too).
        unset($data['slug']);

        return DB::transaction(function () use ($page, $data, $admin) {
            $this->snapshot($page, $admin);

            $page->update($data + [
                'version' => $page->version + 1,
                'last_reviewed_at' => now(),
                'updated_by' => $admin->id,
            ]);
            $this->audit->log('admin.page.updated', "Updated page {$page->title} to v{$page->version}", $page, [], $admin->id);

            return $page->fresh();
        });
    }

    /** Restore a prior version's content — itself recorded as a new version, never a silent rewrite. */
    public function restoreRevision(Page $page, PageRevision $revision, User $admin): Page
    {
        abort_unless($revision->page_id === $page->id, 404);

        return DB::transaction(function () use ($page, $revision, $admin) {
            $this->snapshot($page, $admin);

            $page->update([
                'title' => $revision->title,
                'type' => $revision->type,
                'category' => $revision->category,
                'excerpt' => $revision->excerpt,
                'plain_summary' => $revision->plain_summary,
                'body' => $revision->body,
                'applicable_services' => $revision->applicable_services,
                'applicable_countries' => $revision->applicable_countries,
                'effective_date' => $revision->effective_date,
                'is_published' => $revision->is_published,
                'version' => $page->version + 1,
                'last_reviewed_at' => now(),
                'updated_by' => $admin->id,
            ]);
            $this->audit->log('admin.page.reverted', "Reverted page {$page->title} to v{$revision->version}", $page, ['restored_version' => $revision->version], $admin->id);

            return $page->fresh();
        });
    }

    private function snapshot(Page $page, User $admin): void
    {
        PageRevision::create([
            'page_id' => $page->id,
            'version' => $page->version,
            'title' => $page->title,
            'slug' => $page->slug,
            'type' => $page->type,
            'category' => $page->category,
            'excerpt' => $page->excerpt,
            'plain_summary' => $page->plain_summary,
            'body' => $page->body,
            'applicable_services' => $page->applicable_services,
            'applicable_countries' => $page->applicable_countries,
            'effective_date' => $page->effective_date,
            'is_published' => $page->is_published,
            'edited_by' => $admin->id,
            'created_at' => now(),
        ]);
    }

    /** Archive-not-delete: soft-deletes so a legal/info page's history and any external links aren't destroyed. */
    public function archive(Page $page, User $admin): void
    {
        $page->update(['is_published' => false, 'updated_by' => $admin->id]);
        $this->audit->log('admin.page.archived', "Archived page {$page->title}", $page, [], $admin->id);
        $page->delete();
    }

    public function restore(Page $page, User $admin): Page
    {
        $page->restore();
        $page->update(['updated_by' => $admin->id]);
        $this->audit->log('admin.page.restored', "Restored page {$page->title}", $page, [], $admin->id);

        return $page->fresh();
    }
}
