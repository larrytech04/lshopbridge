<?php

namespace Tests\Feature\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_non_admin_cannot_view_pages(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.pages.index'))->assertForbidden();
    }

    public function test_normal_edit_without_a_slug_field_updates_successfully(): void
    {
        $page = Page::factory()->create(['slug' => 'terms']);

        // Matches the real form: the slug input is disabled on edit, so browsers
        // never submit it — the request simply has no "slug" key at all.
        $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => 'Terms of Service (Updated)', 'type' => 'legal', 'body' => 'New body text.',
        ])->assertRedirect();

        $page->refresh();
        $this->assertSame('terms', $page->slug);
        $this->assertSame('Terms of Service (Updated)', $page->title);
    }

    public function test_submitting_a_slug_on_update_is_rejected_outright(): void
    {
        $page = Page::factory()->create(['slug' => 'terms', 'title' => 'Original']);

        // A non-empty "slug" on an update is treated as tampering (the field is
        // supposed to be disabled client-side) and the whole request is
        // rejected rather than silently applying the rest of the change.
        $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => 'Should Not Apply', 'slug' => 'attempted-new-slug', 'type' => 'legal', 'body' => 'x',
        ])->assertSessionHasErrors('slug');

        $page->refresh();
        $this->assertSame('terms', $page->slug);
        $this->assertSame('Original', $page->title);
    }

    public function test_updating_a_page_creates_a_revision_and_bumps_the_version(): void
    {
        $page = Page::factory()->create(['title' => 'Original Title', 'body' => 'Original body.', 'version' => 1]);

        $this->actingAs($this->admin())->put(route('admin.pages.update', $page), [
            'title' => 'New Title', 'type' => 'legal', 'body' => 'New body.',
        ])->assertRedirect();

        $page->refresh();
        $this->assertSame(2, $page->version);
        $this->assertSame('New Title', $page->title);

        $this->assertDatabaseHas('page_revisions', [
            'page_id' => $page->id, 'version' => 1, 'title' => 'Original Title', 'body' => 'Original body.',
        ]);
    }

    public function test_publishing_never_overwrites_a_revision_a_second_time(): void
    {
        $page = Page::factory()->create(['title' => 'v1', 'version' => 1]);

        $admin = $this->admin();
        $this->actingAs($admin)->put(route('admin.pages.update', $page), ['title' => 'v2', 'type' => 'legal', 'body' => 'b2']);
        $this->actingAs($admin)->put(route('admin.pages.update', $page), ['title' => 'v3', 'type' => 'legal', 'body' => 'b3']);

        $this->assertSame(3, $page->fresh()->version);
        $this->assertSame(2, $page->revisions()->count());
    }

    public function test_admin_can_restore_a_prior_revision(): void
    {
        $page = Page::factory()->create(['title' => 'Original', 'body' => 'Original body.']);
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.pages.update', $page), ['title' => 'Changed', 'type' => 'legal', 'body' => 'Changed body.']);
        $revision = $page->revisions()->first();

        $this->actingAs($admin)->post(route('admin.pages.revisions.restore', [$page, $revision]))->assertRedirect();

        $page->refresh();
        $this->assertSame('Original', $page->title);
        $this->assertSame('Original body.', $page->body);
        $this->assertSame(3, $page->version); // v1 -> v2 (changed) -> v3 (restore)
    }

    public function test_archive_soft_deletes_and_restore_brings_it_back(): void
    {
        $page = Page::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.pages.destroy', $page))->assertRedirect();
        $this->assertSoftDeleted('pages', ['id' => $page->id]);

        $this->actingAs($this->admin())->post(route('admin.pages.restore', $page))->assertRedirect();
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'deleted_at' => null]);
    }

    public function test_public_page_renders_meta_description(): void
    {
        // type: info specifically — a legal-type page now redirects to the
        // Legal Center (see LegalCenterTest), so this generic "/p/{slug}
        // renders" case is exercised with the type that still serves directly.
        $page = Page::factory()->create(['slug' => 'company-story', 'type' => 'info', 'meta_description' => 'Our refund policy explained.']);

        $this->get(route('pages.show', $page))
            ->assertOk()
            ->assertSee('Our refund policy explained.', false);
    }
}
