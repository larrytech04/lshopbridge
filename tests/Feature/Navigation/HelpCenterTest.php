<?php

namespace Tests\Feature\Navigation;

use App\Models\Faq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_the_help_center(): void
    {
        $this->get(route('help.index'))->assertRedirect(route('login'));
    }

    public function test_only_published_faqs_are_shown(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        Faq::create(['question' => 'Visible question', 'answer' => 'Answer', 'category' => 'general', 'is_published' => true, 'sort' => 1]);
        Faq::create(['question' => 'Hidden question', 'answer' => 'Answer', 'category' => 'general', 'is_published' => false, 'sort' => 2]);

        $response = $this->actingAs($user)->get(route('help.index'));

        $response->assertOk();
        $response->assertSee('Visible question');
        $response->assertDontSee('Hidden question');
    }

    public function test_search_filters_faqs_by_question_or_answer(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        Faq::create(['question' => 'How do I deposit?', 'answer' => 'Go to the deposit page.', 'category' => 'deposits', 'is_published' => true, 'sort' => 1]);
        Faq::create(['question' => 'How do I withdraw?', 'answer' => 'Go to the withdraw page.', 'category' => 'withdrawals', 'is_published' => true, 'sort' => 2]);

        $response = $this->actingAs($user)->get(route('help.index', ['q' => 'deposit']));

        $response->assertOk();
        $response->assertSee('How do I deposit?');
        $response->assertDontSee('How do I withdraw?');
    }
}
