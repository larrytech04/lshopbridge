<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Guide;
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

        return view('public.guides.index', [
            'guides' => $query->latest()->paginate(9)->withQueryString(),
            'featured' => Guide::published()->where('is_featured', true)->take(2)->get(),
            'category' => $category,
        ]);
    }

    public function show(Guide $guide): View
    {
        abort_unless($guide->is_published, 404);
        $guide->increment('views');

        return view('public.guides.show', [
            'guide' => $guide,
            'related' => Guide::published()->where('id', '!=', $guide->id)
                ->where('category', $guide->category)->take(3)->get(),
        ]);
    }
}
