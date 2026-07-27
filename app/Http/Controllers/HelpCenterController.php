<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpCenterController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $faqs = Faq::published()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('question', 'like', "%{$q}%")->orWhere('answer', 'like', "%{$q}%")))
            ->get()
            ->groupBy('category');

        return view('help.index', ['faqs' => $faqs, 'q' => $q]);
    }
}
