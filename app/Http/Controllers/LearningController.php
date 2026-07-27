<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningController extends Controller
{
    /** The academy's table of contents, shared with the public academy page (see Guide::academySections). */
    private function sections(): array
    {
        return Guide::academySections();
    }

    public function index(Request $request): View
    {
        $query = Guide::published();
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $guides = $query->get();
        $sections = $this->sections();

        $grouped = collect($sections)->map(
            fn ($categories) => $guides->whereIn('category', $categories)->sortBy(fn ($g) => array_search($g->category, $categories))
        )->filter(fn ($g) => $g->isNotEmpty());

        return view('dashboard.learning.index', [
            'grouped' => $grouped,
            'allCategories' => $guides->pluck('category')->unique()->values(),
            'category' => $category,
            'totalGuides' => Guide::published()->count(),
        ]);
    }

    public function show(Guide $guide): View
    {
        abort_unless($guide->is_published, 404);
        $guide->increment('views');

        // Sequential prev/next within the guide's own section, so reading feels like a book.
        $ownSection = collect($this->sections())->first(fn ($cats) => in_array($guide->category, $cats));
        $siblings = $ownSection
            ? Guide::published()->whereIn('category', $ownSection)->get()->sortBy(fn ($g) => array_search($g->category, $ownSection))->values()
            : collect();
        $pos = $siblings->search(fn ($g) => $g->id === $guide->id);

        return view('dashboard.learning.show', [
            'guide' => $guide,
            'prev' => $pos !== false && $pos > 0 ? $siblings[$pos - 1] : null,
            'next' => $pos !== false && $pos < $siblings->count() - 1 ? $siblings[$pos + 1] : null,
            'related' => Guide::published()->where('id', '!=', $guide->id)->where('category', $guide->category)->take(3)->get(),
            'alreadyVoted' => (bool) session("guide_feedback_{$guide->id}"),
        ]);
    }
}
