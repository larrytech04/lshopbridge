<?php

namespace Tests\Feature\Admin;

use App\Models\FormFingerprint;
use App\Models\FormSecurityEvent;
use App\Models\SpamReviewCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityEventsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'status' => 'active']);
    }

    public function test_index_renders_with_real_stats_and_no_fake_numbers(): void
    {
        FormSecurityEvent::create([
            'reference' => 'FSE-TEST0001', 'event_type' => 'form.honeypot_triggered', 'form_type' => 'contact',
            'risk_level' => 'high', 'action_taken' => 'silently_discarded', 'triggered_rules' => ['honeypot_triggered'],
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.security-events.index'));

        $response->assertOk();
        $response->assertSeeText('Security events');
        $response->assertSeeText('Not configured');
    }

    public function test_events_log_can_be_filtered_by_risk_level(): void
    {
        FormSecurityEvent::create(['reference' => 'FSE-A', 'event_type' => 'form.honeypot_triggered', 'form_type' => 'contact', 'risk_level' => 'critical', 'action_taken' => 'silently_discarded']);
        FormSecurityEvent::create(['reference' => 'FSE-B', 'event_type' => 'form.rate_limit_exceeded', 'form_type' => 'newsletter', 'risk_level' => 'low', 'action_taken' => 'rate_limited']);

        $response = $this->actingAs($this->admin())->get(route('admin.security-events.index', ['tab' => 'events', 'risk_level' => 'critical']));

        $response->assertOk();
        $response->assertSeeText('FSE-A');
        $response->assertDontSeeText('FSE-B');
    }

    public function test_marking_an_event_as_false_positive_records_the_reviewer(): void
    {
        $admin = $this->admin();
        $event = FormSecurityEvent::create(['reference' => 'FSE-FP', 'event_type' => 'form.honeypot_triggered', 'form_type' => 'contact', 'risk_level' => 'high', 'action_taken' => 'silently_discarded']);

        $response = $this->actingAs($admin)->post(route('admin.security-events.false-positive', $event), ['note' => 'Known partner IP']);

        $response->assertRedirect();
        $event->refresh();
        $this->assertSame('false_positive', $event->status);
        $this->assertSame($admin->id, $event->reviewed_by);
    }

    public function test_marking_a_review_case_as_spam(): void
    {
        $admin = $this->admin();
        $case = SpamReviewCase::create(['reference' => 'SRC-1', 'form_type' => 'contact', 'status' => 'pending_review', 'risk_level' => 'medium']);

        $response = $this->actingAs($admin)->post(route('admin.security-events.review.spam', $case));

        $response->assertRedirect();
        $this->assertSame('spam', $case->fresh()->status);
    }

    public function test_marking_a_review_case_legitimate_records_the_reviewer(): void
    {
        $admin = $this->admin();
        $case = SpamReviewCase::create(['reference' => 'SRC-2', 'form_type' => 'contact', 'status' => 'pending_review', 'risk_level' => 'medium']);

        $this->actingAs($admin)->post(route('admin.security-events.review.legitimate', $case));

        $case->refresh();
        $this->assertSame('legitimate', $case->status);
        $this->assertSame($admin->id, $case->reviewed_by);
    }

    public function test_blocklisting_a_fingerprint_from_a_review_case(): void
    {
        $fingerprint = FormFingerprint::create([
            'fingerprint_hash' => 'abc123', 'form_types' => ['contact'], 'ip_hashes' => ['x'],
            'occurrence_count' => 3, 'first_seen_at' => now(), 'last_seen_at' => now(), 'blocked' => false,
        ]);
        $case = SpamReviewCase::create(['reference' => 'SRC-3', 'form_type' => 'contact', 'status' => 'pending_review', 'risk_level' => 'medium', 'fingerprint_hash' => 'abc123']);

        $this->actingAs($this->admin())->post(route('admin.security-events.review.block-fingerprint', $case));

        $this->assertTrue($fingerprint->fresh()->blocked);
    }

    public function test_allowing_a_sender_domain_creates_an_allowlist_entry(): void
    {
        $case = SpamReviewCase::create(['reference' => 'SRC-4', 'form_type' => 'contact', 'status' => 'pending_review', 'risk_level' => 'medium', 'sender_email' => 'ceo@trustedpartner.com']);

        $this->actingAs($this->admin())->post(route('admin.security-events.review.allow-sender', $case));

        $this->assertDatabaseHas('form_allowlist_entries', ['subject_type' => 'email_domain', 'subject_value' => 'trustedpartner.com']);
    }

    public function test_removing_an_allowlist_entry(): void
    {
        $entry = \App\Models\FormAllowlistEntry::create(['subject_type' => 'ip', 'subject_value' => '1.2.3.4']);

        $response = $this->actingAs($this->admin())->delete(route('admin.security-events.allowlist.destroy', $entry));

        $response->assertRedirect();
        $this->assertDatabaseMissing('form_allowlist_entries', ['id' => $entry->id]);
    }

    public function test_a_regular_user_cannot_access_security_events(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $response = $this->actingAs($user)->get(route('admin.security-events.index'));

        $response->assertForbidden();
    }
}
