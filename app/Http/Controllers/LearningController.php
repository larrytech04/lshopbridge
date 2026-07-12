<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningController extends Controller
{
    public function index(Request $request): View
    {
        $query = Guide::published();
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return view('dashboard.learning.index', [
            'guides' => $query->latest()->paginate(9)->withQueryString(),
            'category' => $category,
        ]);
    }

    public function show(Guide $guide): View
    {
        abort_unless($guide->is_published, 404);
        $guide->increment('views');

        return view('dashboard.learning.show', [
            'guide' => $guide,
            'related' => Guide::published()->where('id', '!=', $guide->id)->take(3)->get(),
        ]);
    }
}
