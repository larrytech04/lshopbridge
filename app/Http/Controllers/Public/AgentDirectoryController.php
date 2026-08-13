<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Country;
use App\Services\Security\FormProtectionService;
use App\Services\Seo\CanonicalUrlService;
use App\Services\Seo\StructuredDataBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Agent::approved()->with(['warehouseCountry', 'countries', 'shippingRates', 'user']);

        if ($search = $request->query('q')) {
            $query->where('business_name', 'like', "%{$search}%");
        }

        if ($country = $request->query('country')) {
            $query->whereHas('countries', fn ($q) => $q->where('countries.id', $country));
        }

        if ($method = $request->query('method')) {
            $query->whereJsonContains('shipping_methods', $method);
        }

        return view('public.agents.index', [
            'agents' => $query->orderByDesc('is_featured')->orderByDesc('rating')->paginate(9)->withQueryString(),
            'countries' => Country::active()->get(),
            'filters' => $request->only('q', 'country', 'method'),
        ]);
    }

    public function show(Agent $agent, CanonicalUrlService $canonical, StructuredDataBuilder $schema): View
    {
        abort_unless($agent->status->value === 'approved', 404);

        $breadcrumbs = [
            ['name' => __('Home'), 'url' => $canonical->normalize(route('home'))],
            ['name' => __('Shipping agents'), 'url' => $canonical->normalize(route('agents.index'))],
            ['name' => $agent->business_name, 'url' => $canonical->normalize(route('agents.show', $agent))],
        ];

        return view('public.agents.show', [
            'agent' => $agent->load(['warehouseCountry', 'countries', 'shippingRates.destinationCountry', 'user']),
            'reviews' => $agent->reviews()->where('status', 'approved')->with('user')->latest()->take(10)->get(),
            'breadcrumbs' => $breadcrumbs,
            'breadcrumbSchema' => $schema->breadcrumbList($breadcrumbs),
        ]);
    }

    /**
     * Anonymous "I dealt with this agent off-platform" feedback. Always
     * lands as pending + is_guest=true — never auto-published, and always
     * visibly marked unverified to moderators, since there's no way to
     * confirm a real transaction happened without an account.
     */
    public function guestReview(Request $request, Agent $agent, FormProtectionService $formProtection)
    {
        $data = $request->validate([
            'guest_name' => ['nullable', 'string', 'max:120'],
            'guest_email' => ['nullable', 'email'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $guard = $formProtection->guard($request, 'review_feedback', ['email' => $data['guest_email'] ?? null, 'message' => $data['comment'] ?? null], [
            'protection_setting_key' => 'reviews_protection',
            'turnstile_action' => 'review_feedback',
        ]);

        if ($guard->outcome === 'rate_limited') {
            return back()->with('error', 'Please wait a moment before submitting again.');
        }

        if ($guard->outcome === 'challenge_required') {
            return back()->withInput()->with('error', 'Please complete the verification below and try again.');
        }

        if ($guard->needsFakeSuccessResponse()) {
            return back()->with('success', 'Thanks! Your review will appear after moderation.');
        }

        $agent->reviews()->create([
            'user_id' => null,
            'is_guest' => true,
            'guest_name' => $data['guest_name'] ?: 'Guest',
            'guest_email' => $data['guest_email'] ?? null,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Thanks! Your review will appear after moderation.');
    }
}
