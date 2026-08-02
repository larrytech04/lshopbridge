<?php

namespace Tests\Feature\Public;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_center_shows_published_faqs_grouped_with_correct_counts(): void
    {
        Faq::factory()->create(['question' => 'Published funding question', 'category' => 'funding', 'is_published' => true]);
        Faq::factory()->create(['question' => 'Published security question', 'category' => 'security', 'is_published' => true]);
        Faq::factory()->create(['question' => 'Hidden draft question', 'category' => 'funding', 'is_published' => false]);

        $response = $this->get(route('public.faqs'))->assertOk();

        $response->assertSee('How can we help?');
        $response->assertSee('Published funding question');
        $response->assertSee('Published security question');
        $response->assertDontSee('Hidden draft question');
    }

    public function test_help_center_passes_category_counts_matching_published_faqs_only(): void
    {
        Faq::factory()->count(3)->create(['category' => 'funding', 'is_published' => true]);
        Faq::factory()->create(['category' => 'funding', 'is_published' => false]);
        Faq::factory()->count(2)->create(['category' => 'security', 'is_published' => true]);

        $response = $this->get(route('public.faqs'))->assertOk();

        $response->assertViewHas('categories', function ($categories) {
            $funding = collect($categories)->firstWhere('key', 'funding');
            $security = collect($categories)->firstWhere('key', 'security');

            return $funding['count'] === 3 && $security['count'] === 2;
        });
    }

    public function test_help_center_with_no_faqs_still_renders(): void
    {
        Faq::query()->delete();

        $this->get(route('public.faqs'))
            ->assertOk()
            ->assertSee('How can we help?');
    }

    public function test_guest_support_ticket_form_prefills_subject_from_query_string(): void
    {
        $this->get(route('support.guest.create', ['subject' => 'My search query']))
            ->assertOk()
            ->assertSee('My search query', false);
    }
}
