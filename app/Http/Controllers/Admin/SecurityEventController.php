<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormAllowlistEntry;
use App\Models\FormFingerprint;
use App\Models\FormSecurityEvent;
use App\Models\ProtectedFormSubmission;
use App\Models\SpamReviewCase;
use App\Models\TemporaryFormRestriction;
use App\Services\Security\SpamReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Trust & Safety → Security Events. One page, three tabs (matching the
 * Platform Settings tabbed pattern): a real-data-only dashboard, the raw
 * event log, and the spam review queue. Every number here comes from
 * protected_form_submissions / form_security_events — nothing is invented.
 */
class SecurityEventController extends Controller
{
    public function index(Request $request): View
    {
        $since = now()->subDays(14);

        $submissionsToday = ProtectedFormSubmission::whereDate('created_at', today());
        $outcomeCounts = (clone $submissionsToday)->select('outcome', DB::raw('count(*) as c'))->groupBy('outcome')->pluck('c', 'outcome');

        $dailyTraffic = ProtectedFormSubmission::where('created_at', '>=', $since)
            ->select(DB::raw("date(created_at) as day"), 'outcome', DB::raw('count(*) as c'))
            ->groupBy('day', 'outcome')
            ->get()
            ->groupBy('day');

        $days = collect(range(0, 13))->map(fn ($i) => today()->subDays(13 - $i)->toDateString());
        $trafficSeries = $days->map(function ($day) use ($dailyTraffic) {
            $rows = $dailyTraffic->get($day, collect());
            $accepted = $rows->firstWhere('outcome', 'accepted')?->c ?? 0;
            $blocked = $rows->whereNotIn('outcome', ['accepted'])->sum('c');

            return ['day' => $day, 'accepted' => $accepted, 'blocked' => $blocked];
        });

        $mostTargetedForms = ProtectedFormSubmission::where('created_at', '>=', $since)
            ->select('form_type', DB::raw('count(*) as c'))->groupBy('form_type')->orderByDesc('c')->limit(6)->get();

        $trafficByCountry = ProtectedFormSubmission::where('created_at', '>=', $since)
            ->whereNotIn('outcome', ['accepted'])
            ->whereNotNull('country')
            ->select('country', DB::raw('count(*) as c'))->groupBy('country')->orderByDesc('c')->limit(6)->get();

        $mostTriggeredRules = FormSecurityEvent::where('created_at', '>=', $since)
            ->get()
            ->flatMap(fn ($e) => $e->triggered_rules ?? [])
            ->countBy()
            ->sortDesc()
            ->take(6);

        return view('admin.security-events.index', [
            'tab' => $request->query('tab', 'overview'),
            'stats' => [
                'submissions_today' => $submissionsToday->count(),
                'accepted' => $outcomeCounts['accepted'] ?? 0,
                'challenge_required' => $outcomeCounts['challenge_required'] ?? 0,
                'turnstile_failures' => FormSecurityEvent::whereDate('created_at', today())->whereJsonContains('triggered_rules', 'turnstile_failed')->count(),
                'honeypot_hits' => FormSecurityEvent::whereDate('created_at', today())->where('event_type', 'form.honeypot_triggered')->count(),
                'silently_discarded' => $outcomeCounts['silently_discarded'] ?? 0,
                'rate_limited' => $outcomeCounts['rate_limited'] ?? 0,
                'duplicates' => FormSecurityEvent::whereDate('created_at', today())->whereJsonContains('triggered_rules', 'duplicate_payload')->count(),
                'held_for_review' => SpamReviewCase::where('status', 'pending_review')->count(),
                'active_restrictions' => TemporaryFormRestriction::active()->count(),
                'false_positive_reports' => FormSecurityEvent::where('status', 'false_positive')->count(),
            ],
            'trafficSeries' => $trafficSeries,
            'mostTargetedForms' => $mostTargetedForms,
            'trafficByCountry' => $trafficByCountry,
            'mostTriggeredRules' => $mostTriggeredRules,
            'events' => $this->filteredEvents($request),
            'reviewCases' => SpamReviewCase::pendingReview()->latest()->paginate(15, ['*'], 'review_page'),
            'allowlist' => FormAllowlistEntry::active()->latest()->get(),
        ]);
    }

    private function filteredEvents(Request $request)
    {
        $query = FormSecurityEvent::with('reviewedBy')->latest();

        if ($type = $request->query('event_type')) {
            $query->where('event_type', $type);
        }
        if ($form = $request->query('form_type')) {
            $query->where('form_type', $form);
        }
        if ($level = $request->query('risk_level')) {
            $query->where('risk_level', $level);
        }

        return $query->paginate(20, ['*'], 'events_page')->withQueryString();
    }

    public function markFalsePositive(Request $request, FormSecurityEvent $securityEvent)
    {
        $securityEvent->update([
            'status' => 'false_positive',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $request->input('note'),
        ]);

        return back()->with('success', 'Marked as a false positive.');
    }

    public function markLegitimate(SpamReviewCase $reviewCase, SpamReviewService $service)
    {
        $service->markLegitimate($reviewCase, auth()->user());

        return back()->with('success', 'Case marked legitimate. Use "Deliver message" to actually create the record if needed.');
    }

    public function markSpam(SpamReviewCase $reviewCase, SpamReviewService $service)
    {
        $service->markSpam($reviewCase, auth()->user());

        return back()->with('success', 'Case marked as spam.');
    }

    public function archive(SpamReviewCase $reviewCase, SpamReviewService $service)
    {
        $service->archive($reviewCase, auth()->user());

        return back()->with('success', 'Case archived.');
    }

    public function blockFingerprint(SpamReviewCase $reviewCase)
    {
        if ($reviewCase->fingerprint_hash) {
            FormFingerprint::where('fingerprint_hash', $reviewCase->fingerprint_hash)->update(['blocked' => true]);
        }

        return back()->with('success', 'Fingerprint blocklisted.');
    }

    public function allowSender(SpamReviewCase $reviewCase)
    {
        if ($reviewCase->sender_email && str_contains($reviewCase->sender_email, '@')) {
            $domain = strtolower(substr(strrchr($reviewCase->sender_email, '@'), 1));
            FormAllowlistEntry::create([
                'subject_type' => 'email_domain',
                'subject_value' => $domain,
                'reason' => "Allowed from review case {$reviewCase->reference}",
                'created_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Sender domain allowlisted.');
    }

    public function removeAllowlistEntry(FormAllowlistEntry $allowlistEntry)
    {
        $allowlistEntry->delete();

        return back()->with('success', 'Allowlist entry removed.');
    }
}
