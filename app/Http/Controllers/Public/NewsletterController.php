<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Services\Security\FormProtectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /** LshopBridge Brief topic checkboxes — keep in sync with partials/footer/newsletter-brief.blade.php. */
    public const INTERESTS = [
        'china_shopping' => 'China Shopping',
        'wallet_funding' => 'Wallet Funding',
        'digital_products' => 'Digital Products',
        'shipping_agents' => 'Shipping & Agents',
        'platform_updates' => 'Platform Updates',
    ];

    public function subscribe(Request $request, FormProtectionService $formProtection): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string', 'in:'.implode(',', array_keys(self::INTERESTS))],
        ]);

        $guard = $formProtection->guard($request, 'newsletter', $data, [
            'protection_setting_key' => 'newsletter_protection',
            'turnstile_action' => 'newsletter',
            'allow_authenticated_bypass' => true,
        ]);

        if ($guard->outcome === 'rate_limited') {
            return back()->with('error', 'Please wait a moment before submitting again.');
        }

        if ($guard->outcome === 'challenge_required') {
            return back()->withInput()->with('error', 'Please complete the verification and try again.');
        }

        if ($guard->needsFakeSuccessResponse()) {
            return back()->with('success', "You're subscribed! Check your inbox for updates.");
        }

        NewsletterSubscriber::updateOrCreate(
            ['email' => $data['email']],
            [
                'status' => 'subscribed', 'source' => 'footer', 'subscribed_at' => now(), 'unsubscribed_at' => null,
                'interests' => array_values($data['interests'] ?? []),
            ],
        );

        return back()->with('success', "You're subscribed! Check your inbox for updates.");
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        if ($subscriber) {
            $subscriber->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
        }

        return redirect()->route('home')->with('success', 'You have been unsubscribed.');
    }
}
