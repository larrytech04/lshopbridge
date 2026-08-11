<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuditLogIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active', 'two_factor_enabled' => true, 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()]);
    }

    public function test_verify_reports_a_valid_chain_when_nothing_was_tampered_with(): void
    {
        $audit = app(AuditLogger::class);
        $audit->log('test.one', 'First entry');
        $audit->log('test.two', 'Second entry');
        $audit->log('test.three', 'Third entry');

        $response = $this->actingAs($this->admin())->post(route('admin.audit.verify'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertStringContainsString('verified', $response->getSession()->get('success'));
    }

    public function test_verify_detects_a_row_altered_after_the_fact(): void
    {
        $audit = app(AuditLogger::class);
        $audit->log('test.one', 'First entry');
        $tampered = AuditLog::where('action', 'test.one')->firstOrFail();
        $audit->log('test.two', 'Second entry');

        // Simulate direct DB tampering: change content without recomputing the hash.
        $tampered->update(['description' => 'Tampered description']);

        $response = $this->actingAs($this->admin())->post(route('admin.audit.verify'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('FAILED', $response->getSession()->get('error'));
    }

    public function test_verify_failure_posts_a_critical_ops_alert_when_configured(): void
    {
        config(['services.discord.webhook_url' => 'https://discord.example/webhook']);
        Http::fake();

        $audit = app(AuditLogger::class);
        $audit->log('test.one', 'First entry');
        $tampered = AuditLog::where('action', 'test.one')->firstOrFail();
        $tampered->update(['description' => 'Tampered description']);

        $this->actingAs($this->admin())->post(route('admin.audit.verify'));

        Http::assertSent(fn ($request) => $request->url() === 'https://discord.example/webhook');
    }

    public function test_verify_success_does_not_post_a_critical_ops_alert(): void
    {
        config(['services.discord.webhook_url' => 'https://discord.example/webhook']);
        Http::fake();

        app(AuditLogger::class)->log('test.one', 'First entry');

        $this->actingAs($this->admin())->post(route('admin.audit.verify'));

        Http::assertNothingSent();
    }

    public function test_each_logged_entry_chains_to_the_previous_hash(): void
    {
        $audit = app(AuditLogger::class);
        $audit->log('test.one');
        $audit->log('test.two');

        $rows = AuditLog::orderBy('id')->get();

        $this->assertNull($rows[0]->prev_hash);
        $this->assertNotNull($rows[0]->hash);
        $this->assertSame($rows[0]->hash, $rows[1]->prev_hash);
        $this->assertNotSame($rows[0]->hash, $rows[1]->hash);
    }
}
