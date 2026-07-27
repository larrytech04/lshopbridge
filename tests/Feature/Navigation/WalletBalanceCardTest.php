<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletBalanceCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_page_shows_available_balance_net_of_locked_funds(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $wallet = $user->primaryWallet();
        $wallet->update(['balance' => 100000, 'locked_balance' => 15000]);

        $response = $this->actingAs($user)->get(route('wallet.index'));

        $response->assertOk();
        $response->assertSee('Available balance');
        // Available (85,000) should render, the full 100,000 balance should not be labelled as available.
        $response->assertSee(number_format(85000));
    }

    public function test_wallet_page_shows_no_locked_note_when_nothing_is_held(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $user->primaryWallet()->update(['balance' => 50000, 'locked_balance' => 0]);

        $response = $this->actingAs($user)->get(route('wallet.index'));

        $response->assertOk();
        $response->assertDontSee('locked in pending');
    }
}
