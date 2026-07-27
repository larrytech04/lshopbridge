<?php

namespace Tests\Feature\Security;

use App\Services\Security\HoneypotValidationService;
use Illuminate\Http\Request;
use Tests\TestCase;

class HoneypotValidationServiceTest extends TestCase
{
    public function test_field_name_is_one_of_the_believable_candidates(): void
    {
        $name = app(HoneypotValidationService::class)->fieldName();

        $this->assertContains($name, ['company_website', 'secondary_email', 'contact_url', 'fax_number']);
    }

    public function test_not_triggered_when_all_candidate_fields_are_empty(): void
    {
        $request = Request::create('/contact', 'POST', ['name' => 'Real Person']);

        $this->assertFalse(app(HoneypotValidationService::class)->triggered($request));
    }

    public function test_triggered_when_the_current_field_name_is_filled(): void
    {
        $service = app(HoneypotValidationService::class);
        $request = Request::create('/contact', 'POST', [$service->fieldName() => 'http://spam.example']);

        $this->assertTrue($service->triggered($request));
    }

    public function test_triggered_even_if_a_non_current_candidate_name_is_filled(): void
    {
        // Guards against a stale scraped form using yesterday's rotated name.
        $request = Request::create('/contact', 'POST', ['fax_number' => 'filled-by-a-bot']);

        $this->assertTrue(app(HoneypotValidationService::class)->triggered($request));
    }
}
