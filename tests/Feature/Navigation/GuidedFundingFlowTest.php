<?php

namespace Tests\Feature\Navigation;

use App\Models\BeneficiaryAccount;
use App\Models\KycLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuidedFundingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function customerWithBeneficiary(array $attrs = []): array
    {
        $user = User::factory()->create($attrs + ['role' => 'user', 'status' => 'active', 'kyc_level' => 1]);
        $user->primaryWallet()->update(['balance' => 100000]);
        KycLevel::updateOrCreate(['level' => 1], ['name' => 'L1', 'is_active' => true, 'currency' => 'XAF', 'per_transaction_limit' => 0, 'daily_limit' => 0, 'monthly_limit' => 0]);
        $beneficiary = BeneficiaryAccount::create(['user_id' => $user->id, 'app_type' => 'alipay', 'account_name' => 'Test', 'account_id' => 'test123', 'status' => 'approved', 'is_default' => true]);

        return [$user, $beneficiary];
    }

    public function test_funding_from_wallet_without_a_pin_set_is_blocked(): void
    {
        [$user, $beneficiary] = $this->customerWithBeneficiary();

        $response = $this->actingAs($user)->post(route('funding.store'), [
            'beneficiary_account_id' => $beneficiary->id, 'amount' => 10000, 'funding_source' => 'wallet',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertDatabaseCount('funding_requests', 0);
    }

    public function test_funding_from_wallet_with_wrong_pin_is_rejected(): void
    {
        [$user, $beneficiary] = $this->customerWithBeneficiary(['transaction_pin' => '1234']);

        $response = $this->actingAs($user)->post(route('funding.store'), [
            'beneficiary_account_id' => $beneficiary->id, 'amount' => 10000, 'funding_source' => 'wallet', 'pin' => '9999',
        ]);

        $response->assertSessionHasErrors('pin');
        $this->assertDatabaseCount('funding_requests', 0);
    }

    public function test_funding_from_wallet_with_correct_pin_succeeds(): void
    {
        [$user, $beneficiary] = $this->customerWithBeneficiary(['transaction_pin' => '1234']);

        $response = $this->actingAs($user)->post(route('funding.store'), [
            'beneficiary_account_id' => $beneficiary->id, 'amount' => 10000, 'funding_source' => 'wallet', 'pin' => '1234',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('funding_requests', ['user_id' => $user->id]);
    }
}
