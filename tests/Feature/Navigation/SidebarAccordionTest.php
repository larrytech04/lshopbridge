<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The five collapsible sidebar sections (Money, Shop, China Services,
 * Account, Help & Learning) are a single-open accordion: whichever section
 * contains the current page starts open, every other one starts closed, and
 * there is no per-section memory to let more than one end up open at once.
 */
class SidebarAccordionTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    public function test_no_section_is_open_on_a_page_outside_all_five(): void
    {
        $response = $this->actingAs($this->user())->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee("openSection: null", false);
    }

    public function test_the_money_section_opens_on_a_page_that_belongs_to_it(): void
    {
        $response = $this->actingAs($this->user())->get(route('wallet.index'));

        $response->assertOk();
        $response->assertSee("openSection: 'money'", false);
    }

    public function test_the_account_section_opens_on_a_page_that_belongs_to_it(): void
    {
        $response = $this->actingAs($this->user())->get(route('security.index'));

        $response->assertOk();
        $response->assertSee("openSection: 'account'", false);
    }

    public function test_only_one_section_data_block_exists_so_only_one_can_ever_be_open(): void
    {
        $response = $this->actingAs($this->user())->get(route('dashboard'));

        $response->assertOk();
        // A single shared x-data scope, not five independent ones — that's
        // what actually makes this an accordion rather than five toggles.
        $this->assertSame(1, substr_count($response->getContent(), 'x-data="{ openSection:'));
    }
}
