<?php

namespace Tests\Feature\Public;

use App\Services\Security\HoneypotValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralLeadFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_legitimate_submission_creates_a_lead(): void
    {
        $response = $this->post(route('referral.store'), [
            'name' => 'Prospective Agent',
            'email' => 'prospect@example.com',
            'message' => 'I run a warehouse in Guangzhou and want to join.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('referral_leads', ['email' => 'prospect@example.com', 'status' => 'new']);
    }

    public function test_honeypot_plus_spam_content_creates_no_lead(): void
    {
        $honeypotField = app(HoneypotValidationService::class)->fieldName();

        $response = $this->post(route('referral.store'), [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'Buy backlinks now! http://a.xyz http://b.xyz http://c.xyz guaranteed profit',
            $honeypotField => 'filled',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('referral_leads', 0);
    }

    public function test_a_double_click_does_not_create_two_leads(): void
    {
        $data = ['name' => 'Prospect', 'email' => 'dup@example.com'];

        $this->post(route('referral.store'), $data);
        $this->post(route('referral.store'), $data);

        $this->assertDatabaseCount('referral_leads', 1);
    }
}
