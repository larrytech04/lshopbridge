<?php

namespace Tests\Feature\Security;

use App\Models\FormSecurityEvent;
use App\Models\Setting;
use App\Services\Security\FormProtectionService;
use App\Services\Security\HoneypotValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FormProtectionServiceTest extends TestCase
{
    use RefreshDatabase;

    /** guard() reads $request->session() (rate limiting keys) — Request::create() alone has none. */
    private function requestWith(array $data): Request
    {
        $request = Request::create('/contact', 'POST', $data);
        $request->setLaravelSession($this->app['session']->driver());

        return $request;
    }

    public function test_a_clean_submission_is_accepted_and_recorded_in_the_ledger(): void
    {
        $request = $this->requestWith(['name' => 'Real', 'email' => 'real@example.com', 'message' => 'Hello, I have a question about fees.']);

        $result = app(FormProtectionService::class)->guard($request, 'contact', ['email' => 'real@example.com', 'message' => 'Hello, I have a question about fees.']);

        $this->assertTrue($result->isAccepted());
        $this->assertDatabaseHas('protected_form_submissions', ['form_type' => 'contact', 'outcome' => 'accepted']);
    }

    public function test_honeypot_alone_does_not_guarantee_a_discard(): void
    {
        $honeypotField = app(HoneypotValidationService::class)->fieldName();
        $request = $this->requestWith([$honeypotField => 'oops', 'email' => 'real@example.com', 'message' => 'A perfectly normal message with no spam signals.']);

        $result = app(FormProtectionService::class)->guard($request, 'contact', ['email' => 'real@example.com', 'message' => 'A perfectly normal message with no spam signals.']);

        // One high-confidence signal alone -> held/challenge, not an outright silent discard.
        $this->assertNotSame('silently_discarded', $result->outcome);
        $this->assertContains('honeypot_triggered', $result->triggeredRules);
    }

    public function test_honeypot_plus_spam_content_is_silently_discarded_and_creates_a_security_event(): void
    {
        $honeypotField = app(HoneypotValidationService::class)->fieldName();
        $spamMessage = 'Buy backlinks now! http://a.xyz http://b.xyz http://c.xyz guaranteed profit';
        $request = $this->requestWith([$honeypotField => 'oops', 'email' => 'bot@spam.com', 'message' => $spamMessage]);

        $result = app(FormProtectionService::class)->guard($request, 'contact', ['email' => 'bot@spam.com', 'message' => $spamMessage]);

        $this->assertSame('silently_discarded', $result->outcome);
        $this->assertDatabaseHas('form_security_events', ['form_type' => 'contact', 'action_taken' => 'silently_discarded']);
    }

    public function test_silent_discard_never_stores_the_full_message_in_the_security_event(): void
    {
        $honeypotField = app(HoneypotValidationService::class)->fieldName();
        $spamMessage = 'SECRET-CANARY-STRING-1234 buy backlinks http://a.xyz http://b.xyz http://c.xyz';
        $request = $this->requestWith([$honeypotField => 'oops', 'email' => 'bot@spam.com', 'message' => $spamMessage]);

        app(FormProtectionService::class)->guard($request, 'contact', ['email' => 'bot@spam.com', 'message' => $spamMessage]);

        $event = FormSecurityEvent::firstOrFail();
        $this->assertStringNotContainsString('SECRET-CANARY-STRING-1234', json_encode($event->toArray()));
    }

    public function test_silent_bot_discard_disabled_holds_instead_of_discarding(): void
    {
        Setting::create(['key' => 'silent_bot_discard_enabled', 'value' => '0', 'type' => 'bool', 'group' => 'general']);
        $honeypotField = app(HoneypotValidationService::class)->fieldName();
        $spamMessage = 'Buy backlinks now! http://a.xyz http://b.xyz http://c.xyz guaranteed profit';
        $request = $this->requestWith([$honeypotField => 'oops', 'email' => 'bot@spam.com', 'message' => $spamMessage]);

        $result = app(FormProtectionService::class)->guard($request, 'contact', ['email' => 'bot@spam.com', 'message' => $spamMessage]);

        $this->assertSame('held', $result->outcome);
        $this->assertDatabaseHas('spam_review_cases', ['form_type' => 'contact', 'status' => 'pending_review']);
    }

    public function test_master_switch_off_allows_everything_with_no_events(): void
    {
        Setting::create(['key' => 'bot_protection_enabled', 'value' => '0', 'type' => 'bool', 'group' => 'general']);
        $honeypotField = app(HoneypotValidationService::class)->fieldName();
        $request = $this->requestWith([$honeypotField => 'oops']);

        $result = app(FormProtectionService::class)->guard($request, 'contact', [], ['protection_setting_key' => 'contact_form_protection']);

        $this->assertTrue($result->isAccepted());
        $this->assertDatabaseCount('form_security_events', 0);
    }

    public function test_per_form_protection_toggle_off_bypasses_that_form_only(): void
    {
        Setting::create(['key' => 'contact_form_protection', 'value' => '0', 'type' => 'bool', 'group' => 'general']);
        $honeypotField = app(HoneypotValidationService::class)->fieldName();
        $request = $this->requestWith([$honeypotField => 'oops']);

        $result = app(FormProtectionService::class)->guard($request, 'contact', [], ['protection_setting_key' => 'contact_form_protection']);

        $this->assertTrue($result->isAccepted());
    }

    public function test_log_only_mode_records_what_would_have_happened_without_blocking(): void
    {
        Setting::create(['key' => 'bot_protection_log_only_mode', 'value' => '1', 'type' => 'bool', 'group' => 'general']);
        $honeypotField = app(HoneypotValidationService::class)->fieldName();
        $spamMessage = 'Buy backlinks now! http://a.xyz http://b.xyz http://c.xyz guaranteed profit';
        $request = $this->requestWith([$honeypotField => 'oops', 'email' => 'bot@spam.com', 'message' => $spamMessage]);

        $result = app(FormProtectionService::class)->guard($request, 'contact', ['email' => 'bot@spam.com', 'message' => $spamMessage]);

        $this->assertTrue($result->isAccepted());
        $this->assertDatabaseHas('form_security_events', ['event_type' => 'form.would_have_silently_discarded', 'action_taken' => 'allowed_log_only']);
    }

    public function test_repeated_extremely_abnormal_rate_triggers_rate_limiting(): void
    {
        $request = $this->requestWith(['email' => 'rl@example.com']);
        $service = app(FormProtectionService::class);

        for ($i = 0; $i < 5; $i++) {
            $service->guard($request, 'contact', ['email' => 'rl@example.com']);
        }
        $result = $service->guard($request, 'contact', ['email' => 'rl@example.com']);

        $this->assertSame('rate_limited', $result->outcome);
    }

    public function test_an_ip_with_an_active_temporary_restriction_is_always_discarded(): void
    {
        \App\Models\TemporaryFormRestriction::create([
            'subject_type' => 'ip',
            'subject_value' => hash_hmac('sha256', '127.0.0.1', config('app.key')),
            'reason' => 'confirmed abuse',
            'expires_at' => now()->addHour(),
        ]);
        $request = $this->requestWith(['email' => 'restricted@example.com']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $result = app(FormProtectionService::class)->guard($request, 'contact', ['email' => 'restricted@example.com']);

        $this->assertSame('silently_discarded', $result->outcome);
    }

    public function test_allowlisted_ip_bypasses_the_honeypot_check(): void
    {
        \App\Models\FormAllowlistEntry::create(['subject_type' => 'ip', 'subject_value' => '127.0.0.1']);
        $honeypotField = app(HoneypotValidationService::class)->fieldName();
        $request = $this->requestWith([$honeypotField => 'oops']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $result = app(FormProtectionService::class)->guard($request, 'contact', []);

        $this->assertTrue($result->isAccepted());
    }
}
