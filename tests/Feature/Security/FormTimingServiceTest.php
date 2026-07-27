<?php

namespace Tests\Feature\Security;

use App\Services\Security\FormTimingService;
use Tests\TestCase;

class FormTimingServiceTest extends TestCase
{
    public function test_a_freshly_issued_token_is_valid(): void
    {
        $service = app(FormTimingService::class);
        $token = $service->issue('contact');

        $this->travel(5)->seconds();
        $result = $service->validate($token, 'contact');

        $this->assertTrue($result->valid);
        $this->assertNull($result->reason);
        $this->assertSame(5, $result->elapsedSeconds);
    }

    public function test_submitting_unrealistically_fast_is_flagged_but_still_valid(): void
    {
        $service = app(FormTimingService::class);
        $token = $service->issue('contact');

        $result = $service->validate($token, 'contact');

        $this->assertTrue($result->valid);
        $this->assertTrue($result->tooFast);
    }

    public function test_a_missing_token_is_invalid(): void
    {
        $result = app(FormTimingService::class)->validate(null, 'contact');

        $this->assertFalse($result->valid);
        $this->assertSame('missing', $result->reason);
    }

    public function test_a_tampered_token_is_rejected(): void
    {
        $service = app(FormTimingService::class);
        $token = $service->issue('contact');

        $result = $service->validate($token.'x', 'contact');

        $this->assertFalse($result->valid);
        $this->assertSame('tampered', $result->reason);
    }

    public function test_a_token_issued_for_a_different_form_is_rejected(): void
    {
        $service = app(FormTimingService::class);
        $token = $service->issue('contact');

        $result = $service->validate($token, 'newsletter');

        $this->assertFalse($result->valid);
        $this->assertSame('form-mismatch', $result->reason);
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $service = app(FormTimingService::class);
        $token = $service->issue('contact');

        $this->travel(61)->minutes();
        $result = $service->validate($token, 'contact');

        $this->assertFalse($result->valid);
        $this->assertSame('expired', $result->reason);
    }

    public function test_a_reused_token_is_rejected_on_the_second_submission(): void
    {
        $service = app(FormTimingService::class);
        $token = $service->issue('contact');

        $first = $service->validate($token, 'contact');
        $second = $service->validate($token, 'contact');

        $this->assertTrue($first->valid);
        $this->assertFalse($second->valid);
        $this->assertSame('reused', $second->reason);
    }
}
