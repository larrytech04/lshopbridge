<?php

namespace App\Http\Controllers\Public;

use App\Enums\GuideFeedbackReason;
use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Services\Admin\GuideAdminService;
use App\Services\Security\FormProtectionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $query = Guide::published();

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        // Same book-like grouping the dashboard Learning Center uses, so the
        // public academy and the logged-in one present one consistent course.
        $guides = $query->get();
        $sections = Guide::academySections();

        $grouped = collect($sections)->map(
            fn ($categories) => $guides->whereIn('category', $categories)->sortBy(fn ($g) => array_search($g->category, $categories))
        )->filter(fn ($g) => $g->isNotEmpty());

        return view('public.guides.index', [
            'grouped' => $grouped,
            'allCategories' => Guide::published()->get()->pluck('category')->unique()->values(),
            'category' => $category,
            'totalGuides' => Guide::published()->count(),
        ]);
    }

    public function show(Guide $guide): View
    {
        abort_unless($guide->is_published, 404);
        $guide->increment('views');

        // Sequential prev/next within the guide's own academy section, same
        // "read it like a book" navigation as the dashboard Learning Center.
        $ownSection = collect(Guide::academySections())->first(fn ($cats) => in_array($guide->category, $cats));
        $siblings = $ownSection
            ? Guide::published()->whereIn('category', $ownSection)->get()->sortBy(fn ($g) => array_search($g->category, $ownSection))->values()
            : collect();
        $pos = $siblings->search(fn ($g) => $g->id === $guide->id);

        return view('public.guides.show', [
            'guide' => $guide,
            'prev' => $pos !== false && $pos > 0 ? $siblings[$pos - 1] : null,
            'next' => $pos !== false && $pos < $siblings->count() - 1 ? $siblings[$pos + 1] : null,
            'related' => Guide::published()->where('id', '!=', $guide->id)
                ->where('category', $guide->category)->take(3)->get(),
            'alreadyVoted' => (bool) session("guide_feedback_{$guide->id}"),
        ]);
    }

    public function feedback(Request $request, Guide $guide, GuideAdminService $service, FormProtectionService $formProtection)
    {
        $data = $request->validate([
            'was_helpful' => ['required', 'boolean'],
            'reason' => ['nullable', 'in:'.implode(',', array_column(GuideFeedbackReason::cases(), 'value'))],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        // Session-scoped, not a hard block — an honest "don't nag the same visitor
        // twice in one session" guard, not a claim of real duplicate-vote prevention.
        if (session("guide_feedback_{$guide->id}")) {
            return back();
        }

        $guard = $formProtection->guard($request, 'guide_feedback', ['comment' => $data['comment'] ?? null], [
            'protection_setting_key' => 'guide_feedback_protection',
            'allow_authenticated_bypass' => true,
        ]);

        if (! $guard->isAccepted()) {
            // Low-stakes widget: still show the normal "thanks" state to avoid
            // tipping off automation, just without recording anything real.
            session(["guide_feedback_{$guide->id}" => true]);

            return back()->with('success', 'Thanks for the feedback!');
        }

        $service->recordFeedback(
            $guide,
            $data['was_helpful'],
            $data['reason'] ?? null,
            $data['comment'] ?? null,
            $request->user(),
            $request->ip(),
        );

        session(["guide_feedback_{$guide->id}" => true]);

        return back()->with('success', 'Thanks for the feedback!');
    }
}
