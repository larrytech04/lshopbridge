<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentLead;
use App\Models\Country;
use App\Services\Security\FormRateLimitService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceController extends Controller
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

        return view('dashboard.marketplace.index', [
            'agents' => $query->orderByDesc('is_featured')->orderByDesc('rating')->paginate(9)->withQueryString(),
            'countries' => Country::active()->get(),
            'filters' => $request->only('q', 'country', 'method'),
        ]);
    }

    public function show(Agent $agent): View
    {
        abort_unless($agent->status->value === 'approved', 404);

        $lead = $agent->leads()
            ->where('user_id', auth()->id())
            ->where('status', '!=', 'closed')
            ->latest()
            ->with('messages.user')
            ->first();

        return view('dashboard.marketplace.show', [
            'agent' => $agent->load('warehouseCountry', 'countries', 'shippingRates.destinationCountry', 'user'),
            'reviews' => $agent->reviews()->where('status', 'approved')->with('user')->latest()->take(15)->get(),
            'lead' => $lead,
        ]);
    }

    public function contact(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'shipping_method' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:1500'],
        ]);

        $lead = $agent->leads()->create([
            'reference' => reference('PB-LEAD'),
            'user_id' => $request->user()->id,
            'shipping_method' => $data['shipping_method'] ?? null,
            'message' => $data['message'],
            'status' => 'new',
        ]);

        $lead->messages()->create([
            'user_id' => $request->user()->id,
            'is_agent' => false,
            'message' => $data['message'],
        ]);

        return back()->with('success', 'Your request was sent to '.$agent->business_name.'.');
    }

    public function sendMessage(Request $request, AgentLead $lead)
    {
        abort_unless($lead->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:1500'],
        ]);

        $lead->messages()->create([
            'user_id' => $request->user()->id,
            'is_agent' => false,
            'message' => $data['message'],
        ]);

        if ($lead->status === 'new') {
            $lead->update(['status' => 'contacted']);
        }

        return back();
    }

    public function pollMessages(Request $request, AgentLead $lead)
    {
        abort_unless($lead->user_id === $request->user()->id, 403);

        return response()->json([
            'messages' => $lead->messages()->with('user')->get()->map(fn ($m) => [
                'is_agent' => $m->is_agent,
                'name' => $m->user->name,
                'message' => $m->message,
                'time' => $m->created_at->diffForHumans(),
            ]),
            'status' => $lead->status,
            'customer_confirmed' => (bool) $lead->customer_confirmed_at,
        ]);
    }

    public function confirmComplete(Request $request, AgentLead $lead)
    {
        abort_unless($lead->user_id === $request->user()->id, 403);
        abort_if($lead->status === 'closed', 422);

        $lead->customer_confirmed_at = now();
        $lead->markCompleted();

        return back()->with('success', 'Thanks for confirming, delivery marked as completed.');
    }

    public function review(Request $request, Agent $agent, FormRateLimitService $rateLimit)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        // Defense-in-depth against a compromised or scripted authenticated
        // session posting spam reviews at scale — login alone isn't a
        // guarantee once a session/token has been hijacked.
        if (setting('reviews_protection', true) && $rateLimit->tooManyAttempts('review_feedback', $request)) {
            return back()->with('error', 'Please wait a moment before submitting again.');
        }
        $rateLimit->hit('review_feedback', $request);

        $agent->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Thanks! Your review will appear after moderation.');
    }
}
