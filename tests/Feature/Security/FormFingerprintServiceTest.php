<?php

namespace Tests\Feature\Security;

use App\Services\Security\FormFingerprintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormFingerprintServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_identical_content_produces_the_same_fingerprint_regardless_of_case_or_spacing(): void
    {
        $service = app(FormFingerprintService::class);

        $a = $service->fingerprint(['email' => 'Spammer@Example.com', 'message' => 'Buy   now!!']);
        $b = $service->fingerprint(['email' => 'spammer@example.com', 'message' => 'buy now!!']);

        $this->assertSame($a, $b);
    }

    public function test_different_content_produces_different_fingerprints(): void
    {
        $service = app(FormFingerprintService::class);

        $a = $service->fingerprint(['email' => 'a@example.com', 'message' => 'hello']);
        $b = $service->fingerprint(['email' => 'b@example.com', 'message' => 'hello']);

        $this->assertNotSame($a, $b);
    }

    public function test_repeated_submissions_from_many_ips_become_suspicious(): void
    {
        $service = app(FormFingerprintService::class);
        $hash = $service->fingerprint(['email' => 'spammer@example.com', 'message' => 'spam']);

        $fingerprint = null;
        for ($i = 0; $i < 6; $i++) {
            $fingerprint = $service->record($hash, 'contact', "ip-hash-{$i}");
        }

        $this->assertTrue($service->isSuspicious($fingerprint));
        $this->assertSame(6, $fingerprint->occurrence_count);
    }

    public function test_a_single_submission_is_not_suspicious(): void
    {
        $service = app(FormFingerprintService::class);
        $hash = $service->fingerprint(['email' => 'real@example.com', 'message' => 'hi']);
        $fingerprint = $service->record($hash, 'contact', 'ip-hash-1');

        $this->assertFalse($service->isSuspicious($fingerprint));
    }

    public function test_an_admin_blocked_fingerprint_is_always_suspicious(): void
    {
        $service = app(FormFingerprintService::class);
        $hash = $service->fingerprint(['email' => 'blocked@example.com']);
        $fingerprint = $service->record($hash, 'contact', 'ip-hash-1');
        $fingerprint->update(['blocked' => true]);

        $this->assertTrue($service->isSuspicious($fingerprint->fresh()));
    }
}
