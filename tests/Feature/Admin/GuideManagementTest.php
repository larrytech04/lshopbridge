<?php

namespace Tests\Feature\Admin;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_non_admin_cannot_view_guides(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.guides.index'))->assertForbidden();
    }

    public function test_admin_can_create_guide_in_a_previously_unavailable_category(): void
    {
        // "xiaohongshu" was one of 9 categories the dashboard Learning Center
        // already grouped by that the old admin form's hardcoded 8-value
        // select simply didn't offer.
        $this->actingAs($this->admin())->post(route('admin.guides.store'), [
            'title' => 'Using Xiaohongshu', 'category' => 'xiaohongshu', 'difficulty' => 'intermediate',
        ])->assertRedirect();

        $this->assertDatabaseHas('guides', ['title' => 'Using Xiaohongshu', 'category' => 'xiaohongshu', 'difficulty' => 'intermediate']);
    }

    public function test_updating_a_guide_never_changes_its_slug(): void
    {
        $guide = Guide::factory()->create(['slug' => 'stable-slug']);

        $this->actingAs($this->admin())->put(route('admin.guides.update', $guide), [
            'title' => 'Renamed Guide', 'category' => 'general', 'difficulty' => 'beginner',
        ])->assertRedirect();

        $this->assertSame('stable-slug', $guide->fresh()->slug);
        $this->assertSame('Renamed Guide', $guide->fresh()->title);
    }

    public function test_archive_soft_deletes_and_restore_brings_it_back(): void
    {
        $guide = Guide::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.guides.destroy', $guide))->assertRedirect();
        $this->assertSoftDeleted('guides', ['id' => $guide->id]);

        $this->actingAs($this->admin())->post(route('admin.guides.restore', $guide))->assertRedirect();
        $this->assertDatabaseHas('guides', ['id' => $guide->id, 'deleted_at' => null]);
    }

    // --------------------------------------------------------------- feedback

    public function test_visitor_can_submit_helpful_feedback(): void
    {
        $guide = Guide::factory()->create();

        $this->post(route('guides.feedback', $guide), ['was_helpful' => 1])->assertRedirect();

        $this->assertDatabaseHas('guide_feedback', ['guide_id' => $guide->id, 'was_helpful' => 1]);
        $this->assertSame(1, $guide->helpfulCount());
    }

    public function test_visitor_can_submit_not_helpful_feedback_with_a_reason(): void
    {
        $guide = Guide::factory()->create();

        $this->post(route('guides.feedback', $guide), ['was_helpful' => 0, 'reason' => 'outdated'])->assertRedirect();

        $this->assertDatabaseHas('guide_feedback', ['guide_id' => $guide->id, 'was_helpful' => 0, 'reason' => 'outdated']);
    }

    public function test_feedback_is_not_recorded_twice_in_the_same_session(): void
    {
        $guide = Guide::factory()->create();

        $this->withSession([])->post(route('guides.feedback', $guide), ['was_helpful' => 1]);
        $this->post(route('guides.feedback', $guide), ['was_helpful' => 1]);

        $this->assertSame(1, $guide->feedback()->count());
    }

    public function test_admin_sees_feedback_summary_on_the_edit_page(): void
    {
        $guide = Guide::factory()->create();
        $guide->feedback()->create(['was_helpful' => false, 'reason' => 'outdated', 'created_at' => now()]);
        $guide->feedback()->create(['was_helpful' => true, 'created_at' => now()]);

        $this->actingAs($this->admin())
            ->get(route('admin.guides.edit', $guide))
            ->assertOk()
            ->assertSee('1 helpful')
            ->assertSee('1 not helpful');
    }

    // --------------------------------------------------------------- SEO

    public function test_guide_show_page_renders_meta_description(): void
    {
        $guide = Guide::factory()->create(['meta_description' => 'A custom SEO description for this guide.']);

        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee('A custom SEO description for this guide.', false);
    }
}
