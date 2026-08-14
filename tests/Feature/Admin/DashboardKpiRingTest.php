<?php

namespace Tests\Feature\Admin;

use App\Models\Deposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The KPI ring cards on the admin Overview dashboard: real per-card colors
 * (never a single shared green/red), the brand red only on a genuinely
 * negative delta, and the live-refresh JSON endpoint that powers the
 * polling in _kpis.blade.php.
 */
class DashboardKpiRingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_the_dashboard_renders_a_ring_and_stable_key_for_every_kpi_card(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('data-role="arc"', false);
        $response->assertSee('data-kpi-key="total-deposited"', false);
        $response->assertSee('data-kpi-key="wallet-liabilities"', false);
        $response->assertSee('kpiLive', false);
    }

    public function test_a_positive_delta_card_uses_its_own_icon_tint_not_a_shared_green(): void
    {
        // "Total users" tint is #3B82F6 (blue) — force a positive delta by
        // having more users now than existed before the period started.
        User::factory()->count(3)->create(['created_at' => now()->subDays(40)]);
        User::factory()->count(2)->create(['created_at' => now()]);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));
        $html = $response->getContent();

        $card = $this->extractCard($html, 'total-users');
        $this->assertStringContainsString('stroke: #3B82F6', $card);
        $this->assertStringNotContainsString('#840a20', $card);
    }

    public function test_a_negative_delta_card_uses_the_brand_red_not_its_own_tint(): void
    {
        // Confirmed deposits: more in the previous period than this one —
        // guarantees a negative delta on "Total deposited" (tint #10B981).
        Deposit::factory()->create(['status' => 'confirmed', 'net_amount' => 1000, 'created_at' => now()->subDays(40)]);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));
        $html = $response->getContent();

        $card = $this->extractCard($html, 'total-deposited');
        $this->assertStringContainsString('stroke: #840a20', $card);
        $this->assertStringNotContainsString('stroke: #10B981', $card);
    }

    public function test_a_null_delta_card_shows_a_dash_not_a_fabricated_percentage(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));
        $html = $response->getContent();

        // Wallet liabilities is a point-in-time balance with an explicitly
        // null delta (see DashboardReportService::kpis()).
        $card = $this->extractCard($html, 'wallet-liabilities');
        $this->assertStringContainsString('>-</span>', $card);
    }

    public function test_the_live_refresh_endpoint_returns_the_same_real_figures(): void
    {
        Deposit::factory()->create(['status' => 'confirmed', 'net_amount' => 5000, 'created_at' => now()]);

        $response = $this->actingAs($this->admin())->getJson(route('admin.dashboard.kpis'));

        $response->assertOk();
        $response->assertJsonStructure(['financial' => [['key', 'value_display', 'delta']], 'customer', 'operational']);
        $row = collect($response->json('financial'))->firstWhere('key', 'total-deposited');
        $this->assertNotNull($row);
        $this->assertSame('5,000 '.config('platform.base_currency', 'XAF'), $row['value_display']);
    }

    public function test_a_guest_cannot_reach_the_live_refresh_endpoint(): void
    {
        $this->getJson(route('admin.dashboard.kpis'))->assertUnauthorized();
    }

    private function extractCard(string $html, string $key): string
    {
        $start = strpos($html, 'data-kpi-key="'.$key.'"');
        $this->assertNotFalse($start, "Card with key {$key} not found in response.");

        return substr($html, $start, 2600);
    }
}
