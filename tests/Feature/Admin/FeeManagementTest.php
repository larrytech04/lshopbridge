<?php

namespace Tests\Feature\Admin;

use App\Models\Fee;
use App\Models\FeeExemption;
use App\Models\FeeHistory;
use App\Models\FeeSchedule;
use App\Models\User;
use App\Services\Admin\FeeAdminService;
use App\Services\Fees\FeeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function engine(): FeeCalculationService
    {
        return app(FeeCalculationService::class);
    }

    // --------------------------------------------------------------- access

    public function test_non_admin_cannot_view_fees(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.fees.index'))->assertForbidden();
    }

    public function test_admin_can_view_fees(): void
    {
        Fee::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.fees.index'))
            ->assertOk()
            ->assertSee('Fees & Charges');
    }

    // ------------------------------------------------------------- calculation

    public function test_percentage_fee_calculation(): void
    {
        Fee::factory()->create(['applies_to' => 'funding', 'type' => 'percent', 'value' => 2.5, 'min_fee' => 0]);

        $result = $this->engine()->calculate(100000, 'funding');

        $this->assertEqualsWithDelta(2500.0, $result['calculated_fee'], 0.01);
    }

    public function test_fixed_fee_calculation(): void
    {
        Fee::factory()->create(['applies_to' => 'deposit', 'type' => 'fixed', 'value' => 500, 'currency' => 'XAF', 'min_fee' => 0]);

        $result = $this->engine()->calculate(50000, 'deposit');

        $this->assertEqualsWithDelta(500.0, $result['calculated_fee'], 0.01);
    }

    public function test_fixed_plus_percentage_fee_calculation(): void
    {
        Fee::factory()->create([
            'applies_to' => 'deposit', 'type' => 'fixed_plus_percent', 'value' => 1.5, 'fixed_value' => 100,
            'currency' => 'XAF', 'min_fee' => 0, 'scope' => 'mtn_momo',
        ]);

        $result = $this->engine()->calculate(10000, 'deposit', ['scope' => 'mtn_momo']);

        // 1.5% of 10000 = 150, + 100 fixed = 250
        $this->assertEqualsWithDelta(250.0, $result['calculated_fee'], 0.01);
    }

    public function test_tiered_fee_calculation_picks_correct_tier(): void
    {
        $fee = Fee::factory()->create(['applies_to' => 'funding', 'type' => 'tiered', 'value' => 0, 'min_fee' => 0]);
        $fee->tiers()->createMany([
            ['min_amount' => 0, 'max_amount' => 50000, 'percent' => 1.5, 'fixed' => 0, 'sort' => 0],
            ['min_amount' => 50000.01, 'max_amount' => 500000, 'percent' => 1.0, 'fixed' => 0, 'sort' => 1],
            ['min_amount' => 500000.01, 'max_amount' => null, 'percent' => 0.75, 'fixed' => 0, 'sort' => 2],
        ]);

        $low = $this->engine()->calculate(20000, 'funding');
        $mid = $this->engine()->calculate(100000, 'funding');
        $high = $this->engine()->calculate(1000000, 'funding');

        $this->assertEqualsWithDelta(300.0, $low['calculated_fee'], 0.01);   // 1.5% of 20000
        $this->assertEqualsWithDelta(1000.0, $mid['calculated_fee'], 0.01); // 1.0% of 100000
        $this->assertEqualsWithDelta(7500.0, $high['calculated_fee'], 0.01); // 0.75% of 1000000
    }

    public function test_minimum_fee_is_enforced(): void
    {
        Fee::factory()->create(['applies_to' => 'funding', 'type' => 'percent', 'value' => 2.5, 'min_fee' => 100]);

        $result = $this->engine()->calculate(1000, 'funding'); // 2.5% of 1000 = 25, below min of 100

        $this->assertEqualsWithDelta(100.0, $result['calculated_fee'], 0.01);
    }

    public function test_maximum_fee_is_enforced(): void
    {
        Fee::factory()->create(['applies_to' => 'funding', 'type' => 'percent', 'value' => 2.5, 'min_fee' => 0, 'max_fee' => 10000]);

        $result = $this->engine()->calculate(10000000, 'funding'); // 2.5% of 10M = 250,000, above max

        $this->assertEqualsWithDelta(10000.0, $result['calculated_fee'], 0.01);
    }

    public function test_fee_never_exceeds_transaction_amount(): void
    {
        Fee::factory()->create(['applies_to' => 'funding', 'type' => 'fixed', 'value' => 5000, 'currency' => 'XAF', 'min_fee' => 0]);

        $result = $this->engine()->calculate(1000, 'funding'); // fixed fee of 5000 on a 1000 transaction

        $this->assertEqualsWithDelta(1000.0, $result['calculated_fee'], 0.01);
    }

    public function test_no_matching_fee_returns_zero(): void
    {
        $result = $this->engine()->calculate(100000, 'funding');

        $this->assertSame(0.0, $result['calculated_fee']);
        $this->assertNull($result['matched_fee_id']);
    }

    // ------------------------------------------------------------ priority / specificity

    public function test_scoped_fee_takes_priority_over_generic_default(): void
    {
        Fee::factory()->create(['applies_to' => 'funding', 'type' => 'percent', 'value' => 2.5, 'min_fee' => 0, 'scope' => null]);
        Fee::factory()->create(['applies_to' => 'funding', 'type' => 'percent', 'value' => 1.0, 'min_fee' => 0, 'scope' => 'alipay']);

        $scoped = $this->engine()->calculate(100000, 'funding', ['scope' => 'alipay']);
        $generic = $this->engine()->calculate(100000, 'funding', ['scope' => 'wechat']);

        $this->assertEqualsWithDelta(1000.0, $scoped['calculated_fee'], 0.01);
        $this->assertEqualsWithDelta(2500.0, $generic['calculated_fee'], 0.01);
    }

    public function test_amount_range_restricts_matching(): void
    {
        Fee::factory()->create(['applies_to' => 'funding', 'type' => 'percent', 'value' => 1.0, 'min_fee' => 0, 'min_amount' => 100000]);

        $below = $this->engine()->calculate(50000, 'funding');
        $above = $this->engine()->calculate(150000, 'funding');

        $this->assertNull($below['matched_fee_id']);
        $this->assertNotNull($above['matched_fee_id']);
    }

    // ------------------------------------------------------------------ exemptions

    public function test_exempted_customer_is_charged_zero(): void
    {
        Fee::factory()->create(['applies_to' => 'funding', 'type' => 'percent', 'value' => 2.5, 'min_fee' => 0]);
        $user = User::factory()->create();
        FeeExemption::factory()->create([
            'exemption_type' => 'customer', 'target_value' => (string) $user->id, 'user_id' => $user->id,
            'start_date' => now()->subDay(), 'end_date' => null,
        ]);

        $result = $this->engine()->calculate(100000, 'funding', ['user' => $user]);

        $this->assertTrue($result['exempt']);
        $this->assertSame(0.0, $result['calculated_fee']);
    }

    public function test_exemption_scoped_to_other_service_does_not_apply(): void
    {
        Fee::factory()->create(['applies_to' => 'funding', 'type' => 'percent', 'value' => 2.5, 'min_fee' => 0]);
        $user = User::factory()->create();
        FeeExemption::factory()->create([
            'exemption_type' => 'customer', 'target_value' => (string) $user->id, 'user_id' => $user->id,
            'applicable_services' => ['deposit'], 'start_date' => now()->subDay(), 'end_date' => null,
        ]);

        $result = $this->engine()->calculate(100000, 'funding', ['user' => $user]);

        $this->assertFalse($result['exempt']);
        $this->assertEqualsWithDelta(2500.0, $result['calculated_fee'], 0.01);
    }

    // ---------------------------------------------------------------- validation

    public function test_percentage_cannot_be_negative(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.fees.store'), ['name' => 'Bad fee', 'applies_to' => 'funding', 'type' => 'percent', 'value' => -1])
            ->assertSessionHasErrors('value');
    }

    public function test_currency_required_for_fixed_fee(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.fees.store'), ['name' => 'Fixed fee', 'applies_to' => 'deposit', 'type' => 'fixed', 'value' => 500])
            ->assertSessionHasErrors('currency');
    }

    public function test_max_fee_cannot_be_lower_than_min_fee(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.fees.store'), [
                'name' => 'Bad limits', 'applies_to' => 'funding', 'type' => 'percent', 'value' => 2.5,
                'min_fee' => 1000, 'max_fee' => 100,
            ])
            ->assertSessionHasErrors('max_fee');
    }

    public function test_percentage_ceiling_is_enforced(): void
    {
        $ceiling = (float) config('platform.risk.max_fee_percent', 20);

        $this->actingAs($this->admin())
            ->post(route('admin.fees.store'), [
                'name' => 'Too high', 'applies_to' => 'funding', 'type' => 'percent', 'value' => $ceiling + 10,
            ])
            ->assertSessionHasErrors('value');
    }

    public function test_duplicate_active_default_fee_is_rejected(): void
    {
        Fee::factory()->create(['applies_to' => 'funding', 'scope' => null, 'is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.fees.store'), ['name' => 'Second default', 'applies_to' => 'funding', 'type' => 'percent', 'value' => 3])
            ->assertSessionHasErrors('applies_to');
    }

    public function test_admin_can_create_a_valid_fee(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.fees.store'), [
                'name' => 'MTN MoMo Deposit Fee', 'applies_to' => 'deposit', 'type' => 'fixed_plus_percent',
                'value' => 1.5, 'fixed_value' => 100, 'currency' => 'XAF', 'scope' => 'mtn_momo',
            ])
            ->assertRedirect(route('admin.fees.index'));

        $this->assertDatabaseHas('fees', ['name' => 'MTN MoMo Deposit Fee']);
        $this->assertDatabaseCount('fee_history', 1);
    }

    // -------------------------------------------------------------- update / history

    public function test_updating_a_fee_snapshots_history_without_deleting_old_rows(): void
    {
        $fee = Fee::factory()->create(['value' => 2.5]);
        $this->assertDatabaseCount('fee_history', 0);

        $this->actingAs($this->admin())
            ->put(route('admin.fees.update', $fee), [
                'name' => $fee->name, 'applies_to' => $fee->applies_to, 'type' => 'percent', 'value' => 2.5, 'confirmed' => '1',
            ])
            ->assertRedirect(route('admin.fees.index'));

        $this->assertDatabaseCount('fee_history', 1);
    }

    public function test_large_fee_change_requires_confirmation(): void
    {
        $fee = Fee::factory()->create(['value' => 2.5]);

        $response = $this->actingAs($this->admin())
            ->from(route('admin.fees.index'))
            ->put(route('admin.fees.update', $fee), [
                'name' => $fee->name, 'applies_to' => $fee->applies_to, 'type' => 'percent', 'value' => 10,
            ]);

        $response->assertSessionHas('warnings');
        $this->assertDatabaseHas('fees', ['id' => $fee->id, 'value' => 2.5]);
    }

    public function test_history_rows_are_never_edited_only_appended(): void
    {
        $fee = Fee::factory()->create(['value' => 2.5]);
        $admin = $this->admin();
        $svc = app(FeeAdminService::class);

        $svc->updateFee($fee, array_merge($fee->only(['name', 'applies_to', 'type']), ['value' => 3.0]), $admin);
        $svc->updateFee($fee->fresh(), array_merge($fee->only(['name', 'applies_to', 'type']), ['value' => 3.5]), $admin);

        $this->assertSame(2, FeeHistory::where('fee_id', $fee->id)->count());
        $this->assertDatabaseHas('fee_history', ['fee_id' => $fee->id, 'value' => 3.0]);
        $this->assertDatabaseHas('fee_history', ['fee_id' => $fee->id, 'value' => 3.5]);
    }

    // ---------------------------------------------------------------- archive

    public function test_archiving_a_fee_soft_deletes_instead_of_hard_deleting(): void
    {
        $fee = Fee::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.fees.destroy', $fee))
            ->assertRedirect();

        $this->assertSoftDeleted('fees', ['id' => $fee->id]);
        $this->assertDatabaseHas('fee_history', ['fee_id' => $fee->id, 'event' => 'archived']);
    }

    // -------------------------------------------------------------- scheduling

    public function test_admin_can_schedule_a_future_fee_change(): void
    {
        $fee = Fee::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.fees.schedules.store'), [
                'fee_id' => $fee->id, 'new_value' => 3.5,
                'effective_start_date' => now()->addDays(5)->toDateString(),
                'reason' => 'Planned quarterly adjustment',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('fee_schedules', ['fee_id' => $fee->id, 'status' => 'scheduled']);
    }

    public function test_conflicting_schedule_for_same_fee_is_rejected(): void
    {
        $fee = Fee::factory()->create();
        FeeSchedule::factory()->create([
            'fee_id' => $fee->id, 'status' => 'scheduled',
            'effective_start_date' => now()->addDays(3), 'effective_end_date' => now()->addDays(10),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.fees.schedules.store'), [
                'fee_id' => $fee->id, 'new_value' => 4,
                'effective_start_date' => now()->addDays(5)->toDateString(),
                'reason' => 'Overlapping attempt',
            ])
            ->assertSessionHasErrors('effective_start_date');
    }

    public function test_due_schedule_is_applied_on_read_and_promoted_into_the_live_fee(): void
    {
        $fee = Fee::factory()->create(['value' => 2.5]);
        FeeSchedule::factory()->create([
            'fee_id' => $fee->id, 'status' => 'scheduled', 'new_value' => 5.0,
            'effective_start_date' => now()->subDay(), 'effective_end_date' => null,
        ]);

        app(FeeAdminService::class)->applyDueSchedules();

        $this->assertDatabaseHas('fees', ['id' => $fee->id, 'value' => 5.0]);
        $this->assertDatabaseHas('fee_schedules', ['fee_id' => $fee->id, 'status' => 'applied']);
    }

    // ---------------------------------------------------------------------- bulk

    public function test_bulk_action_does_not_allow_editing_fee_values(): void
    {
        $fee = Fee::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('admin.fees.bulk-action'), ['action' => 'deactivate', 'ids' => [$fee->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('fees', ['id' => $fee->id, 'is_active' => false, 'value' => $fee->value]);
    }

    public function test_bulk_action_rejects_unknown_action_names(): void
    {
        $fee = Fee::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.fees.bulk-action'), ['action' => 'set_value', 'ids' => [$fee->id]])
            ->assertSessionHasErrors('action');
    }

    // ------------------------------------------------------------------- decimal precision

    public function test_percentage_calculation_preserves_decimal_precision(): void
    {
        Fee::factory()->create(['applies_to' => 'funding', 'type' => 'percent', 'value' => 1.2345, 'min_fee' => 0]);

        $result = $this->engine()->calculate(1000000, 'funding');

        $this->assertEqualsWithDelta(12345.0, $result['calculated_fee'], 0.01);
    }

    // ------------------------------------------------------------------ provider-passed honesty

    public function test_provider_passed_fee_warns_about_no_live_integration(): void
    {
        $check = app(FeeAdminService::class)->validateFee([
            'applies_to' => 'funding', 'type' => 'provider_passed', 'value' => 2.0, 'provider_markup_percent' => 0.5,
        ]);

        $this->assertTrue($check['ok']);
        $this->assertNotEmpty($check['warnings']);
    }

    // ------------------------------------------------------------------- transaction integrity

    public function test_deposit_freezes_fee_snapshot_at_creation(): void
    {
        Fee::factory()->create(['applies_to' => 'deposit', 'type' => 'percent', 'value' => 2.5, 'min_fee' => 0]);
        $user = User::factory()->create(['status' => 'active']);
        $method = \App\Models\PaymentMethod::create([
            'code' => 'test_method', 'name' => 'Test Method', 'type' => 'momo', 'currency' => 'XAF',
        ]);

        $deposit = app(\App\Services\Deposit\DepositService::class)->createManual($user, $method, 100000);

        $this->assertNotNull($deposit->fee_id);
        $this->assertEqualsWithDelta(2500.0, (float) $deposit->fee, 0.01);

        // Editing the fee afterward must not change what was already charged.
        Fee::find($deposit->fee_id)->update(['value' => 10]);

        $this->assertEqualsWithDelta(2500.0, (float) $deposit->fresh()->fee, 0.01);
    }
}
