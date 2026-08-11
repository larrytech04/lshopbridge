<?php

namespace Tests\Feature\Admin;

use App\Enums\ExchangeRateMarginType;
use App\Models\ExchangeRate;
use App\Models\ExchangeRateHistory;
use App\Models\ExchangeRateSchedule;
use App\Models\User;
use App\Services\Admin\ExchangeRateAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    // --------------------------------------------------------------- access

    public function test_non_admin_cannot_view_exchange_rates(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.rates.index'))->assertForbidden();
    }

    public function test_admin_can_view_exchange_rates(): void
    {
        ExchangeRate::factory()->create(['base_currency' => 'XAF', 'quote_currency' => 'CNY']);

        $this->actingAs($this->admin())
            ->get(route('admin.rates.index'))
            ->assertOk()
            ->assertSee('Exchange Rates');
    }

    // ------------------------------------------------------- effective rate

    public function test_effective_rate_percentage_margin(): void
    {
        $rate = ExchangeRate::computeEffectiveRate(0.0121, ExchangeRateMarginType::Percentage, 1.5);

        $this->assertEqualsWithDelta(0.0121 * 0.985, $rate, 0.0000001);
    }

    public function test_effective_rate_fixed_margin(): void
    {
        $rate = ExchangeRate::computeEffectiveRate(0.0121, ExchangeRateMarginType::Fixed, 0, 0.0005);

        $this->assertEqualsWithDelta(0.0116, $rate, 0.0000001);
    }

    public function test_effective_rate_custom_override(): void
    {
        $rate = ExchangeRate::computeEffectiveRate(0.0121, ExchangeRateMarginType::Custom, 0, null, 0.0115);

        $this->assertEqualsWithDelta(0.0115, $rate, 0.0000001);
    }

    public function test_effective_rate_preserves_decimal_precision(): void
    {
        $rate = ExchangeRate::computeEffectiveRate(0.01234567, ExchangeRateMarginType::Percentage, 1.5);

        $this->assertEqualsWithDelta(0.01234567 * 0.985, $rate, 0.00000001);
    }

    // ---------------------------------------------------------- validation

    public function test_cannot_create_rate_with_identical_source_and_destination(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.rates.store'), [
                'base_currency' => 'XAF', 'quote_currency' => 'XAF', 'rate' => 0.01,
            ])
            ->assertSessionHasErrors('quote_currency');

        $this->assertDatabaseCount('exchange_rates', 0);
    }

    public function test_cannot_create_duplicate_active_pair(): void
    {
        ExchangeRate::factory()->create(['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.rates.store'), [
                'base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.013,
            ])
            ->assertSessionHasErrors('quote_currency');

        $this->assertDatabaseCount('exchange_rates', 1);
    }

    public function test_rate_must_be_greater_than_zero(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.rates.store'), [
                'base_currency' => 'NGN', 'quote_currency' => 'CNY', 'rate' => 0,
            ])
            ->assertSessionHasErrors();
    }

    public function test_margin_percent_cannot_exceed_configured_ceiling(): void
    {
        $ceiling = (float) config('platform.risk.max_margin_percent', 10);

        $this->actingAs($this->admin())
            ->post(route('admin.rates.store'), [
                'base_currency' => 'GHS', 'quote_currency' => 'CNY', 'rate' => 0.48,
                'margin_type' => 'percentage', 'margin_percent' => $ceiling + 5,
            ])
            ->assertSessionHasErrors('margin_percent');
    }

    public function test_admin_can_create_a_valid_rate(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.rates.store'), [
                'base_currency' => 'xaf', 'quote_currency' => 'cny', 'rate' => 0.0121,
                'margin_type' => 'percentage', 'margin_percent' => 1.5,
            ])
            ->assertRedirect(route('admin.rates.index'));

        $this->assertDatabaseHas('exchange_rates', ['base_currency' => 'XAF', 'quote_currency' => 'CNY']);
        $this->assertDatabaseCount('exchange_rate_history', 1);
    }

    // -------------------------------------------------------------- update / history

    public function test_updating_a_rate_snapshots_history_without_deleting_old_rows(): void
    {
        $rate = ExchangeRate::factory()->create(['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.0121]);
        $this->assertDatabaseCount('exchange_rate_history', 0);

        $this->actingAs($this->admin())
            ->put(route('admin.rates.update', $rate), [
                'base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.0121,
                'margin_type' => 'percentage', 'margin_percent' => 1.5, 'confirmed' => '1',
            ])
            ->assertRedirect(route('admin.rates.index'));

        $this->assertDatabaseCount('exchange_rate_history', 1);
        $this->assertDatabaseHas('exchange_rates', ['id' => $rate->id, 'margin_percent' => 1.5]);
    }

    public function test_large_effective_rate_change_requires_confirmation(): void
    {
        $rate = ExchangeRate::factory()->create(['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.0121, 'margin_percent' => 1.5]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.rates.index'))
            ->put(route('admin.rates.update', $rate), [
                'base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.02,
                'margin_type' => 'percentage', 'margin_percent' => 1.5,
            ]);

        $response->assertSessionHas('warnings');
        $this->assertDatabaseHas('exchange_rates', ['id' => $rate->id, 'rate' => 0.0121]);
    }

    public function test_large_effective_rate_change_applies_once_confirmed(): void
    {
        $rate = ExchangeRate::factory()->create(['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.0121, 'margin_percent' => 1.5]);

        $this->actingAs($this->admin())
            ->put(route('admin.rates.update', $rate), [
                'base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.02,
                'margin_type' => 'percentage', 'margin_percent' => 1.5, 'confirmed' => '1',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('exchange_rates', ['id' => $rate->id, 'rate' => 0.02]);
    }

    // ---------------------------------------------------------------- archive

    public function test_archiving_a_rate_soft_deletes_instead_of_hard_deleting(): void
    {
        $rate = ExchangeRate::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.rates.destroy', $rate))
            ->assertRedirect();

        $this->assertSoftDeleted('exchange_rates', ['id' => $rate->id]);
        $this->assertDatabaseHas('exchange_rate_history', ['exchange_rate_id' => $rate->id, 'event' => 'archived']);
    }

    // -------------------------------------------------------------- scheduling

    public function test_admin_can_schedule_a_future_rate_change(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.rates.schedules.store'), [
                'base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.014,
                'margin_type' => 'percentage', 'margin_percent' => 1.5,
                'effective_from' => now()->addDays(5)->toDateString(),
                'reason' => 'Planned quarterly adjustment',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('exchange_rate_schedules', ['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'status' => 'scheduled']);
    }

    public function test_conflicting_schedule_for_same_pair_is_rejected(): void
    {
        ExchangeRateSchedule::factory()->create([
            'base_currency' => 'XAF', 'quote_currency' => 'CNY', 'status' => 'scheduled',
            'effective_from' => now()->addDays(3), 'effective_to' => now()->addDays(10),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.rates.schedules.store'), [
                'base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.015,
                'margin_type' => 'percentage', 'margin_percent' => 1.5,
                'effective_from' => now()->addDays(5)->toDateString(),
                'reason' => 'Overlapping attempt',
            ])
            ->assertSessionHasErrors('effective_from');
    }

    public function test_due_schedule_is_applied_on_read_and_promoted_into_the_live_rate(): void
    {
        ExchangeRate::factory()->create(['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.0121]);
        ExchangeRateSchedule::factory()->create([
            'base_currency' => 'XAF', 'quote_currency' => 'CNY', 'status' => 'scheduled',
            'rate' => 0.02, 'margin_percent' => 1.5, 'margin_type' => 'percentage',
            'effective_from' => now()->subDay(), 'effective_to' => null,
        ]);

        app(ExchangeRateAdminService::class)->applyDueSchedules();

        $this->assertDatabaseHas('exchange_rates', ['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.02]);
        $this->assertDatabaseHas('exchange_rate_schedules', ['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'status' => 'applied']);
    }

    public function test_admin_can_cancel_a_scheduled_change(): void
    {
        $schedule = ExchangeRateSchedule::factory()->create(['status' => 'scheduled']);

        $this->actingAs($this->admin())
            ->post(route('admin.rates.schedules.cancel', $schedule))
            ->assertRedirect();

        $this->assertDatabaseHas('exchange_rate_schedules', ['id' => $schedule->id, 'status' => 'cancelled']);
    }

    // ------------------------------------------------------------- provider fallback

    public function test_rate_service_falls_back_to_one_when_no_active_rate_or_setting_exists(): void
    {
        $quote = app(\App\Services\Funding\RateService::class)->quote(100000, 'XAF', 'CNY');

        $this->assertSame(1.0, $quote['effective_rate']);
        $this->assertSame(100000.0, $quote['delivered_amount']);
    }

    public function test_due_schedule_takes_priority_over_the_stored_active_rate_in_quote(): void
    {
        ExchangeRate::factory()->create(['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.0121, 'margin_percent' => 1.5, 'is_active' => true]);
        ExchangeRateSchedule::factory()->create([
            'base_currency' => 'XAF', 'quote_currency' => 'CNY', 'status' => 'scheduled',
            'rate' => 0.02, 'margin_percent' => 0, 'margin_type' => 'percentage',
            'effective_from' => now()->subDay(), 'effective_to' => null,
        ]);

        $quote = app(\App\Services\Funding\RateService::class)->quote(1000, 'XAF', 'CNY');

        $this->assertSame(0.02, $quote['base_rate']);
    }

    public function test_marking_provider_source_without_integration_produces_a_warning_not_an_error(): void
    {
        $check = app(ExchangeRateAdminService::class)->validateRate([
            'base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.0121,
            'margin_type' => 'percentage', 'margin_percent' => 1.5, 'rate_source' => 'provider',
        ]);

        $this->assertTrue($check['ok']);
        $this->assertNotEmpty($check['warnings']);
    }

    // -------------------------------------------------------------------- history

    public function test_history_rows_are_never_edited_only_appended(): void
    {
        $rate = ExchangeRate::factory()->create(['rate' => 0.0121]);
        $admin = $this->admin();

        app(ExchangeRateAdminService::class)->updateRate($rate, array_merge($rate->only(['base_currency', 'quote_currency', 'margin_type', 'margin_percent']), ['rate' => 0.0125]), $admin);
        app(ExchangeRateAdminService::class)->updateRate($rate->fresh(), array_merge($rate->only(['base_currency', 'quote_currency', 'margin_type', 'margin_percent']), ['rate' => 0.013]), $admin);

        $this->assertSame(2, ExchangeRateHistory::where('exchange_rate_id', $rate->id)->count());
        $this->assertDatabaseHas('exchange_rate_history', ['exchange_rate_id' => $rate->id, 'rate' => 0.0125]);
        $this->assertDatabaseHas('exchange_rate_history', ['exchange_rate_id' => $rate->id, 'rate' => 0.013]);
    }

    // ----------------------------------------------------------------- calculator

    public function test_calculator_endpoint_reuses_rate_service(): void
    {
        ExchangeRate::factory()->create(['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.0121, 'margin_percent' => 1.5, 'is_active' => true]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.rates.calculate'), [
                'amount' => 100000, 'base_currency' => 'XAF', 'quote_currency' => 'CNY',
            ])
            ->assertOk();

        $expected = app(\App\Services\Funding\RateService::class)->quote(100000, 'XAF', 'CNY');
        $response->assertJson(['effective_rate' => $expected['effective_rate']]);
    }

    // --------------------------------------------------------------------- bulk

    public function test_bulk_action_does_not_allow_editing_rate_values(): void
    {
        $rate = ExchangeRate::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.rates.bulk-action'), [
                'action' => 'deactivate', 'ids' => [$rate->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('exchange_rates', ['id' => $rate->id, 'is_active' => false, 'rate' => $rate->rate]);
    }

    public function test_bulk_action_rejects_unknown_action_names(): void
    {
        $rate = ExchangeRate::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.rates.bulk-action'), [
                'action' => 'set_rate', 'ids' => [$rate->id],
            ])
            ->assertSessionHasErrors('action');
    }
}
