<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Dispute;
use App\Models\User;
use App\Notifications\AdminMessage;
use App\Notifications\AgentVerified;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'all');
        $items = $this->filteredQuery($request, $tab)->paginate(15)->withQueryString();

        return view('admin.agents.index', [
            'items' => $items,
            'tab' => $tab,
            'q' => $request->query('q', ''),
            'countries' => \App\Models\Country::orderBy('name')->get(['id', 'name']),
            'counts' => $this->tabCounts(),
        ]);
    }

    public function show(Agent $agent): View
    {
        return view('admin.agents.show', [
            'agent' => $agent->load('user', 'warehouseCountry', 'countries', 'shippingRates', 'reviews.user'),
        ]);
    }

    public function rowDetail(Agent $agent)
    {
        $agent->load(['user', 'warehouseCountry', 'countries', 'reviews' => fn ($q) => $q->latest()->limit(10), 'reviews.user']);

        $leads = $agent->leads();
        $active = (clone $leads)->whereIn('status', ['new', 'contacted', 'in_progress'])->count();
        $closed = (clone $leads)->where('status', 'closed')->count();
        $completed = (clone $leads)->where('status', 'completed')->count();

        $firstReplyHours = $agent->leads()->with(['messages' => fn ($q) => $q->oldest()->limit(1)])->get()
            ->map(function ($lead) {
                $firstReply = $lead->messages->first();

                return $firstReply ? $lead->created_at->diffInHours($firstReply->created_at) : null;
            })->filter(fn ($h) => $h !== null);

        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $monthly[] = [
                'label' => $start->format('M'),
                'count' => (clone $leads)->where('status', 'completed')
                    ->whereBetween('updated_at', [$start, $start->copy()->endOfMonth()])->count(),
            ];
        }

        return response()->json([
            'agent' => [
                'id' => $agent->id,
                'business_name' => $agent->business_name,
                'slug' => $agent->slug,
                'agent_type' => $agent->agent_type->label(),
                'owner' => $agent->user?->name,
                'email' => $agent->user?->email,
                'phone' => $agent->phone,
                'whatsapp' => $agent->whatsapp,
                'country' => $agent->warehouseCountry?->name,
                'city' => $agent->warehouse_city,
                'address' => $agent->warehouse_address,
                'joined' => $agent->created_at->format('M j, Y'),
                'status' => $agent->status->value,
                'status_label' => $agent->status->label(),
                'rating' => (float) $agent->rating,
                'reviews_count' => $agent->reviews_count,
                'bio' => $agent->bio,
                'cities' => $agent->cities,
                'shipping_methods' => $agent->shipping_methods,
                'is_featured' => $agent->is_featured,
                'featured_from' => $agent->featured_from?->toDateString(),
                'featured_until' => $agent->featured_until?->toDateString(),
                'featured_priority' => $agent->featured_priority,
                'featured_label' => $agent->featured_label,
                'admin_notes' => $agent->admin_notes,
                'checklist' => [
                    'identity' => $agent->status->value === 'approved' && (bool) $agent->id_doc_path,
                    'phone' => (bool) $agent->user?->phone_verified_at,
                    'email' => (bool) $agent->user?->email_verified_at,
                    'business' => (bool) $agent->business_doc_path,
                    'address' => null,
                    'payment' => null,
                    'documents' => (bool) ($agent->business_doc_path && $agent->id_doc_path),
                ],
            ],
            'performance' => [
                'total_leads' => $agent->leads()->count(),
                'completed' => $completed,
                'active' => $active,
                'closed' => $closed,
                'success_rate' => $agent->successRate(),
                'avg_response_hours' => $firstReplyHours->count() ? round($firstReplyHours->avg(), 1) : null,
                'monthly' => $monthly,
            ],
            'reviews' => $agent->reviews->map(fn ($r) => [
                'customer' => $r->user?->name ?? 'Unknown',
                'rating' => $r->rating,
                'comment' => $r->comment,
                'order' => $r->order_reference,
                'date' => $r->created_at->format('M j, Y'),
            ]),
        ]);
    }

    public function approve(Agent $agent, AuditLogger $audit)
    {
        $agent->update(['status' => 'approved', 'verified_at' => now(), 'verified_by' => auth()->id()]);
        $agent->user->update(['kyc_level' => max($agent->user->kyc_level, 3)]);

        $audit->log('admin.agent.approved', "Approved agent {$agent->business_name}", $agent);
        $agent->user->notify(new AgentVerified($agent, true));

        return back()->with('success', 'Agent approved and listed.');
    }

    public function reject(Request $request, Agent $agent, AuditLogger $audit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $agent->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

        $audit->log('admin.agent.rejected', "Rejected agent {$agent->business_name}", $agent, $data);
        $agent->user->notify(new AgentVerified($agent, false, $data['reason']));

        return back()->with('success', 'Agent rejected.');
    }

    public function suspend(Request $request, Agent $agent, AuditLogger $audit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $agent->update(['status' => 'suspended', 'rejection_reason' => $data['reason'], 'is_featured' => false]);

        $audit->log('admin.agent.suspended', "Suspended agent {$agent->business_name}", $agent, $data);
        $agent->user?->notify(new AdminMessage('Your agent account has been suspended', $data['reason'], true));

        return back()->with('success', 'Agent suspended.');
    }

    public function restore(Agent $agent, AuditLogger $audit)
    {
        $agent->update(['status' => 'approved']);
        $audit->log('admin.agent.restored', "Restored agent {$agent->business_name}", $agent);
        $agent->user?->notify(new AdminMessage('Your agent account has been restored', 'Your agent account is active again and visible in the marketplace.', true));

        return back()->with('success', 'Agent restored.');
    }

    public function requestInfo(Request $request, Agent $agent, AuditLogger $audit)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:1000']]);
        $audit->log('admin.agent.info_requested', "Requested more information from {$agent->business_name}", $agent, $data);
        $agent->user?->notify(new AdminMessage('More information needed for your agent application', $data['message'], true));

        return back()->with('success', 'Request sent to the agent.');
    }

    public function toggleFeature(Agent $agent, AuditLogger $audit)
    {
        if (! $agent->is_featured && $agent->status->value !== 'approved') {
            return back()->with('error', 'Only approved agents can be featured.');
        }

        $agent->update(['is_featured' => ! $agent->is_featured]);
        $audit->log($agent->is_featured ? 'admin.agent.featured' : 'admin.agent.unfeatured', "{$agent->business_name} featured toggled", $agent);

        return back()->with('success', $agent->is_featured ? 'Agent featured.' : 'Agent un-featured.');
    }

    public function updateFeatureSettings(Request $request, Agent $agent, AuditLogger $audit)
    {
        if ($agent->status->value !== 'approved') {
            return back()->with('error', 'Only approved agents can be featured.');
        }

        $data = $request->validate([
            'featured_from' => ['nullable', 'date'],
            'featured_until' => ['nullable', 'date', 'after_or_equal:featured_from'],
            'featured_priority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'featured_label' => ['nullable', 'string', 'max:40'],
        ]);

        $agent->update($data + ['is_featured' => true]);
        $audit->log('admin.agent.featured_settings_updated', "Updated featured settings for {$agent->business_name}", $agent, $data);

        return back()->with('success', 'Featured settings saved.');
    }

    public function updateNotes(Request $request, Agent $agent)
    {
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:5000']]);
        $agent->update($data);

        return back()->with('success', 'Notes saved.');
    }

    public function notify(Request $request, Agent $agent)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
        ]);
        $agent->user?->notify(new AdminMessage($data['subject'], $data['body'], true));

        return back()->with('success', 'Message sent.');
    }

    public function bulkAction(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['approve', 'suspend', 'restore', 'feature', 'unfeature', 'notify'])],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'reason' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $agents = Agent::whereIn('id', $data['ids'])->get();

        foreach ($agents as $agent) {
            match ($data['action']) {
                'approve' => $agent->update(['status' => 'approved', 'verified_at' => now(), 'verified_by' => auth()->id()]),
                'suspend' => $agent->update(['status' => 'suspended', 'is_featured' => false, 'rejection_reason' => $data['reason'] ?? null]),
                'restore' => $agent->update(['status' => 'approved']),
                'feature' => $agent->status->value === 'approved' ? $agent->update(['is_featured' => true]) : null,
                'unfeature' => $agent->update(['is_featured' => false]),
                'notify' => $agent->user?->notify(new AdminMessage($data['subject'] ?? 'Update from LshopBridge', $data['body'] ?? '', true)),
                default => null,
            };
            $audit->log('admin.agent.bulk_'.$data['action'], "Bulk {$data['action']} on {$agent->business_name}", $agent);
        }

        return back()->with('success', ucfirst($data['action']).' applied to '.$agents->count().' agent(s).');
    }

    public function destroy(Request $request, Agent $agent, AuditLogger $audit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $audit->log('admin.agent.deleted', "Deleted agent {$agent->business_name}", $agent, $data);
        $agent->delete();

        return redirect()->route('admin.agents.index')->with('success', 'Agent removed.');
    }

    public function exportCsv(Request $request, AuditLogger $audit): StreamedResponse
    {
        $rows = $this->filteredQuery($request, $request->query('tab', 'all'))->get();
        $audit->log('admin.agent.exported', 'Exported '.$rows->count().' agent(s) to CSV');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Business', 'Owner', 'Email', 'Agent type', 'Country', 'City', 'Rating', 'Reviews', 'Completed orders', 'Status', 'Featured', 'Joined']);
            foreach ($rows as $a) {
                fputcsv($out, [
                    $a->id, $a->business_name, $a->user?->name, $a->user?->email, $a->agent_type->label(),
                    $a->warehouseCountry?->name, $a->warehouse_city, $a->rating, $a->reviews_count,
                    $a->completed_orders, $a->status->label(), $a->is_featured ? 'Yes' : 'No', $a->created_at->toDateString(),
                ]);
            }
            fclose($out);
        }, 'agents-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function filteredQuery(Request $request, string $tab): Builder
    {
        $query = Agent::with('user', 'warehouseCountry');

        match ($tab) {
            'pending', 'approved', 'rejected', 'suspended' => $query->where('status', $tab),
            'featured' => $query->where('is_featured', true),
            default => null,
        };

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $w) use ($search) {
                $w->where('business_name', 'like', "%{$search}%")
                    ->orWhere('warehouse_city', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('warehouseCountry', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($type = $request->query('agent_type')) {
            $query->where('agent_type', $type);
        }
        if ($country = $request->query('country_id')) {
            $query->where('warehouse_country_id', $country);
        }
        if ($rating = $request->query('rating_min')) {
            $query->where('rating', '>=', $rating);
        }
        if ($request->query('featured') === '1') {
            $query->where('is_featured', true);
        }
        if ($from = $request->query('joined_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('joined_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->latest();
    }

    private function tabCounts(): array
    {
        return [
            'all' => Agent::count(),
            'pending' => Agent::where('status', 'pending')->count(),
            'approved' => Agent::where('status', 'approved')->count(),
            'rejected' => Agent::where('status', 'rejected')->count(),
            'suspended' => Agent::where('status', 'suspended')->count(),
            'featured' => Agent::where('is_featured', true)->count(),
            'active_month' => Agent::whereHas('leads', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))->count(),
            'avg_rating' => round((float) Agent::where('reviews_count', '>', 0)->avg('rating'), 2),
            'open_complaints' => Dispute::where('category', 'agent')->whereIn('status', ['open', 'in_progress'])->count(),
        ];
    }
}
