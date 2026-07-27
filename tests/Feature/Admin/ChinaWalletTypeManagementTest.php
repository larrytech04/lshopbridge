<?php

namespace Tests\Feature\Admin;

use App\Models\BeneficiaryAccount;
use App\Models\ChinaWalletType;
use App\Models\Country;
use App\Models\KycLevel;
use App\Models\User;
use App\Services\Funding\FundingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ChinaWalletTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    /** A funding customer with an unlimited base KYC allowance, so only the wallet-type limit under test can trip. */
    private function fundedCustomer(int $kycLevel = 0): User
    {
        KycLevel::create([
            'level' => $kycLevel, 'name' => "Level {$kycLevel}", 'is_active' => true,
            'per_transaction_limit' => 0, 'daily_limit' => 0, 'monthly_limit' => 0, 'currency' => 'XAF',
        ]);
        $user = User::factory()->create(['role' => 'user', 'status' => 'active', 'kyc_level' => $kycLevel]);
        $user->primaryWallet()->update(['balance' => 10_000_000]);

        return $user;
    }

    // --------------------------------------------------------------- admin CRUD

    public function test_non_admin_cannot_view_wallet_types(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.china-wallet-types.index'))->assertForbidden();
    }

    public function test_admin_can_create_wallet_type(): void
    {
        $this->actingAs($this->admin())->post(route('admin.china-wallet-types.store'), [
            'code' => 'alipay', 'name' => 'Alipay', 'account_identifier_type' => 'custom',
        ])->assertRedirect();

        $this->assertDatabaseHas('china_wallet_types', ['code' => 'alipay', 'name' => 'Alipay']);
    }

    public function test_updating_never_changes_the_code(): void
    {
        $wallet = ChinaWalletType::factory()->create(['code' => 'alipay']);

        $this->actingAs($this->admin())->put(route('admin.china-wallet-types.update', $wallet), [
            'code' => 'wechat', 'name' => 'Alipay Updated', 'account_identifier_type' => 'custom',
        ])->assertRedirect();

        $this->assertSame('alipay', $wallet->fresh()->code);
    }

    // --------------------------------------------------------------- FundingService enforcement

    public function test_funding_below_wallet_type_minimum_is_rejected(): void
    {
        ChinaWalletType::factory()->create(['code' => 'alipay', 'min_funding_amount' => 5000]);
        $user = $this->fundedCustomer();
        $beneficiary = BeneficiaryAccount::factory()->for($user)->approved()->create(['app_type' => 'alipay']);

        $this->expectException(ValidationException::class);
        app(FundingService::class)->createFromWallet($user, $beneficiary, 1000);
    }

    public function test_funding_above_wallet_type_maximum_is_rejected(): void
    {
        ChinaWalletType::factory()->create(['code' => 'alipay', 'max_funding_amount' => 20000]);
        $user = $this->fundedCustomer();
        $beneficiary = BeneficiaryAccount::factory()->for($user)->approved()->create(['app_type' => 'alipay']);

        $this->expectException(ValidationException::class);
        app(FundingService::class)->createFromWallet($user, $beneficiary, 50000);
    }

    public function test_funding_within_wallet_type_bounds_succeeds(): void
    {
        ChinaWalletType::factory()->create(['code' => 'alipay', 'min_funding_amount' => 1000, 'max_funding_amount' => 100000]);
        $user = $this->fundedCustomer();
        $beneficiary = BeneficiaryAccount::factory()->for($user)->approved()->create(['app_type' => 'alipay']);

        $funding = app(FundingService::class)->createFromWallet($user, $beneficiary, 10000);

        $this->assertNotNull($funding->id);
    }

    public function test_funding_exceeding_daily_limit_is_rejected_on_the_second_request(): void
    {
        ChinaWalletType::factory()->create(['code' => 'alipay', 'daily_limit' => 15000]);
        $user = $this->fundedCustomer();
        $beneficiary = BeneficiaryAccount::factory()->for($user)->approved()->create(['app_type' => 'alipay']);

        app(FundingService::class)->createFromWallet($user, $beneficiary, 10000);

        $this->expectException(ValidationException::class);
        app(FundingService::class)->createFromWallet($user, $beneficiary, 10000);
    }

    public function test_funding_below_wallet_type_min_kyc_level_is_rejected(): void
    {
        ChinaWalletType::factory()->create(['code' => 'alipay', 'min_kyc_level' => 2]);
        $user = $this->fundedCustomer(kycLevel: 0);
        $beneficiary = BeneficiaryAccount::factory()->for($user)->approved()->create(['app_type' => 'alipay']);

        $this->expectException(ValidationException::class);
        app(FundingService::class)->createFromWallet($user, $beneficiary, 5000);
    }

    public function test_funding_blocked_by_country_restriction(): void
    {
        $allowedCountry = Country::factory()->create(['iso2' => 'GH']);
        ChinaWalletType::factory()->create(['code' => 'alipay', 'country_restrictions' => ['GH']]);
        $user = $this->fundedCustomer();
        $user->update(['country_id' => Country::factory()->create(['iso2' => 'CM'])->id]);
        $beneficiary = BeneficiaryAccount::factory()->for($user)->approved()->create(['app_type' => 'alipay']);

        $this->expectException(ValidationException::class);
        app(FundingService::class)->createFromWallet($user, $beneficiary, 5000);
    }

    public function test_no_wallet_type_row_means_no_additional_restriction(): void
    {
        $user = $this->fundedCustomer();
        $beneficiary = BeneficiaryAccount::factory()->for($user)->approved()->create(['app_type' => 'alipay']);

        $funding = app(FundingService::class)->createFromWallet($user, $beneficiary, 5000);

        $this->assertNotNull($funding->id);
    }
}
