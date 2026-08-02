<?php

namespace Tests\Feature\Public;

use App\Enums\BeneficiaryStatus;
use App\Models\BeneficiaryAccount;
use App\Models\ChinaWalletType;
use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundAlipayPageTest extends TestCase
{
    use RefreshDatabase;

    private function seedWallets(): void
    {
        ChinaWalletType::factory()->create(['code' => 'alipay', 'name' => 'Alipay', 'automated_funding' => true, 'manual_funding' => false, 'sort' => 0]);
        ChinaWalletType::factory()->create(['code' => 'wechat', 'name' => 'WeChat Pay', 'sort' => 1]);
        // A row whose code isn't one of AppType's fixed values must never leak onto the page.
        ChinaWalletType::factory()->create(['code' => 'not_a_real_wallet', 'name' => 'Rogue Wallet', 'sort' => 2]);
        // An inactive wallet type must never be offered either.
        ChinaWalletType::factory()->create(['code' => 'other', 'name' => 'Other China wallet', 'is_active' => false, 'sort' => 3]);
    }

    public function test_guest_sees_the_funding_page_with_only_active_known_wallet_types(): void
    {
        $this->seedWallets();

        $response = $this->get(route('public.fund'))->assertOk();

        $response->assertSee('Alipay');
        $response->assertSee('WeChat Pay');
        $response->assertDontSee('Rogue Wallet');
        $response->assertDontSee('Other China wallet');
        $response->assertSee('Create Account to Continue');
    }

    public function test_guest_calculator_shows_estimate_language_not_a_locked_quote(): void
    {
        $this->seedWallets();

        $this->get(route('public.fund'))
            ->assertOk()
            ->assertSee('Estimate')
            ->assertSee('Estimate only. Your final exchange rate, fees and recipient amount will be confirmed before payment.');
    }

    public function test_unverified_customer_is_prompted_to_complete_verification(): void
    {
        $this->seedWallets();
        $user = User::factory()->create(['kyc_level' => 0, 'status' => 'active']);

        $this->actingAs($user)->get(route('public.fund'))
            ->assertOk()
            ->assertSee('Complete Verification');
    }

    public function test_verified_customer_without_approved_beneficiary_is_prompted_to_add_a_wallet(): void
    {
        $this->seedWallets();
        $user = User::factory()->create(['kyc_level' => 1, 'status' => 'active']);
        BeneficiaryAccount::factory()->create(['user_id' => $user->id, 'status' => BeneficiaryStatus::Pending]);

        $this->actingAs($user)->get(route('public.fund'))
            ->assertOk()
            ->assertSee('Add your first China wallet');
    }

    public function test_eligible_customer_sees_fund_now(): void
    {
        $this->seedWallets();
        $user = User::factory()->create(['kyc_level' => 1, 'status' => 'active']);
        BeneficiaryAccount::factory()->create(['user_id' => $user->id, 'status' => BeneficiaryStatus::Approved]);

        $this->actingAs($user)->get(route('public.fund'))
            ->assertOk()
            ->assertSee('Fund Now')
            ->assertSee('Continue with This Quote');
    }

    public function test_calculator_endpoint_returns_a_full_backend_computed_breakdown(): void
    {
        ExchangeRate::factory()->create(['base_currency' => 'XAF', 'quote_currency' => 'CNY', 'rate' => 0.012, 'margin_percent' => 0, 'is_active' => true]);

        $response = $this->postJson(route('calculator'), ['amount' => 100000, 'app_type' => 'alipay'])
            ->assertOk();

        $response->assertJsonStructure([
            'source_amount', 'source_currency', 'fee', 'fee_id', 'fee_snapshot',
            'total_charged', 'exchange_rate', 'base_rate', 'margin_amount',
            'rate_updated_at', 'rate_available', 'target_amount', 'target_currency',
        ]);
        $response->assertJson([
            'source_amount' => 100000.0,
            'exchange_rate' => 0.012,
            'target_amount' => 1200.0,
            'rate_available' => true,
        ]);
    }

    public function test_calculator_endpoint_rejects_invalid_amounts(): void
    {
        $this->postJson(route('calculator'), ['amount' => 0])->assertStatus(422);
        $this->postJson(route('calculator'), ['amount' => -50])->assertStatus(422);
        $this->postJson(route('calculator'), [])->assertStatus(422);
    }
}
