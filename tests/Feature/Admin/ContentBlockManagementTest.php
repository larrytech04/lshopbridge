<?php

namespace Tests\Feature\Admin;

use App\Models\Faq;
use App\Models\ProcessStep;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentBlockManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_non_admin_cannot_view_content_page(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.content.index'))->assertForbidden();
    }

    public function test_admin_can_manage_a_named_text_block(): void
    {
        $this->actingAs($this->admin())->put(route('admin.content.update'), [
            'cms_home_features_title' => 'Custom features heading',
        ])->assertRedirect();

        $this->assertSame('Custom features heading', setting('cms_home_features_title'));
    }

    // --------------------------------------------------------------- testimonials

    public function test_admin_can_create_and_update_testimonial(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.content.testimonials.store'), [
            'name' => 'Jane Doe', 'source' => 'trustpilot', 'rating' => 5, 'text' => 'Great service.',
        ])->assertRedirect();

        $testimonial = Testimonial::where('name', 'Jane Doe')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.content.testimonials.update', $testimonial), [
            'name' => 'Jane D.', 'source' => 'google', 'rating' => 4.5, 'text' => 'Still great.', 'is_active' => 0,
        ])->assertRedirect();

        $testimonial->refresh();
        $this->assertSame('Jane D.', $testimonial->name);
        $this->assertFalse($testimonial->is_active);
    }

    public function test_admin_can_delete_testimonial(): void
    {
        $testimonial = Testimonial::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.content.testimonials.destroy', $testimonial))->assertRedirect();

        $this->assertDatabaseMissing('testimonials', ['id' => $testimonial->id]);
    }

    public function test_inactive_testimonial_is_excluded_from_the_homepage(): void
    {
        Testimonial::factory()->create(['name' => 'Visible One', 'is_active' => true]);
        Testimonial::factory()->create(['name' => 'Hidden One', 'is_active' => false]);

        $this->get(route('home'))
            ->assertSee('Visible One')
            ->assertDontSee('Hidden One');
    }

    // --------------------------------------------------------------- process steps

    public function test_admin_can_create_process_step(): void
    {
        $this->actingAs($this->admin())->post(route('admin.content.steps.store'), [
            'group' => 'fund_step', 'icon' => 'Money-Wallet-1.png', 'title' => 'New Step', 'body' => 'Do the thing.',
        ])->assertRedirect();

        $this->assertDatabaseHas('process_steps', ['group' => 'fund_step', 'title' => 'New Step']);
    }

    public function test_how_it_works_page_reflects_admin_managed_steps(): void
    {
        ProcessStep::factory()->create(['group' => 'fund_step', 'title' => 'Custom Fund Step', 'sort' => 0]);

        $this->get(route('how-it-works'))->assertOk()->assertSee('Custom Fund Step');
    }

    // --------------------------------------------------------------- FAQ audit

    public function test_faq_changes_are_audited(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.faqs.store'), [
            'question' => 'Is this safe?', 'answer' => 'Yes.', 'category' => 'security',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.faq.created']);

        $faq = Faq::where('question', 'Is this safe?')->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.faqs.destroy', $faq))->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.faq.deleted']);
    }
}
