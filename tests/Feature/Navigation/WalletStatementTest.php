<?php

namespace Tests\Feature\Navigation;

use App\Models\User;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_download_a_statement(): void
    {
        $this->get(route('wallet.statement'))->assertRedirect(route('login'));
    }

    public function test_statement_only_contains_the_current_users_transactions(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $other = User::factory()->create(['role' => 'user', 'status' => 'active']);
        app(WalletService::class)->credit($user->primaryWallet(), 5000, 'deposit', null, 'Mine');
        app(WalletService::class)->credit($other->primaryWallet(), 9999, 'deposit', null, 'Not mine');

        $response = $this->actingAs($user)->get(route('wallet.statement'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Mine', $content);
        $this->assertStringNotContainsString('Not mine', $content);
    }
}
