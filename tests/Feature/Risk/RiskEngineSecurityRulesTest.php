<?php

namespace Tests\Feature\Risk;

use App\Models\LoginAttempt;
use App\Models\RiskRule;
use App\Models\User;
use App\Services\Risk\RiskEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskEngineSecurityRulesTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => 'user', 'status' => 'active']);
    }

    private function recordLogin(User $user, bool $newDevice): void
    {
        LoginAttempt::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'successful' => true,
            'was_new_device' => $newDevice,
            'created_at' => now(),
        ]);
    }

    public function test_new_device_high_value_rule_trips_for_a_large_amount_after_a_new_device_login(): void
    {
        RiskRule::create(['code' => 'new_device_high_value', 'name' => 'x', 'action' => 'review', 'severity' => 'medium', 'is_active' => true, 'params' => ['amount' => 1000]]);
        $user = $this->user();
        $this->recordLogin($user, newDevice: true);

        $result = app(RiskEngine::class)->evaluate($user, 5000, 'deposit');

        $this->assertTrue($result['requires_review']);
        $this->assertCount(1, $result['flags']);
        $this->assertSame('new_device_high_value', $result['flags'][0]->rule_code);
    }

    public function test_new_device_high_value_rule_does_not_trip_for_a_known_device(): void
    {
        RiskRule::create(['code' => 'new_device_high_value', 'name' => 'x', 'action' => 'review', 'severity' => 'medium', 'is_active' => true, 'params' => ['amount' => 1000]]);
        $user = $this->user();
        $this->recordLogin($user, newDevice: false);

        $result = app(RiskEngine::class)->evaluate($user, 5000, 'deposit');

        $this->assertFalse($result['requires_review']);
        $this->assertCount(0, $result['flags']);
    }

    public function test_new_device_high_value_rule_does_not_trip_below_the_amount_threshold(): void
    {
        RiskRule::create(['code' => 'new_device_high_value', 'name' => 'x', 'action' => 'review', 'severity' => 'medium', 'is_active' => true, 'params' => ['amount' => 1000]]);
        $user = $this->user();
        $this->recordLogin($user, newDevice: true);

        $result = app(RiskEngine::class)->evaluate($user, 100, 'deposit');

        $this->assertFalse($result['requires_review']);
    }

    public function test_password_reset_then_transaction_rule_trips_within_the_window(): void
    {
        RiskRule::create(['code' => 'password_reset_then_transaction', 'name' => 'x', 'action' => 'review', 'severity' => 'high', 'is_active' => true, 'params' => ['window_hours' => 24]]);
        $user = $this->user();
        $user->forceFill(['password_changed_at' => now()->subHours(2)])->save();

        $result = app(RiskEngine::class)->evaluate($user, 100, 'deposit');

        $this->assertTrue($result['requires_review']);
        $this->assertSame('password_reset_then_transaction', $result['flags'][0]->rule_code);
    }

    public function test_password_reset_then_transaction_rule_does_not_trip_outside_the_window(): void
    {
        RiskRule::create(['code' => 'password_reset_then_transaction', 'name' => 'x', 'action' => 'review', 'severity' => 'high', 'is_active' => true, 'params' => ['window_hours' => 24]]);
        $user = $this->user();
        $user->forceFill(['password_changed_at' => now()->subDays(3)])->save();

        $result = app(RiskEngine::class)->evaluate($user, 100, 'deposit');

        $this->assertFalse($result['requires_review']);
    }

    public function test_password_reset_then_transaction_rule_does_not_trip_when_password_never_changed(): void
    {
        RiskRule::create(['code' => 'password_reset_then_transaction', 'name' => 'x', 'action' => 'review', 'severity' => 'high', 'is_active' => true, 'params' => ['window_hours' => 24]]);
        $user = $this->user();

        $result = app(RiskEngine::class)->evaluate($user, 100, 'deposit');

        $this->assertFalse($result['requires_review']);
    }
}
