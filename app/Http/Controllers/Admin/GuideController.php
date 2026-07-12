<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(): View
    {
        return view('admin.guides.index', ['guides' => Guide::orderBy('sort')->latest()->get()]);
    }

    public function create(): View
    {
        return view('admin.guides.form', ['guide' => new Guide]);
    }

    public function store(Request $request)
    {
        Guide::create($this->validated($request));

        return redirect()->route('admin.guides.index')->with('success', 'Guide created.');
    }

    public function edit(Guide $guide): View
    {
        return view('admin.guides.form', ['guide' => $guide]);
    }

    public function update(Request $request, Guide $guide)
    {
        $guide->update($this->validated($request, $guide));

        return redirect()->route('admin.guides.index')->with('success', 'Guide updated.');
    }

    public function destroy(Guide $guide)
    {
        $guide->delete();

        return back()->with('success', 'Guide removed.');
    }

    private function validated(Request $request, ?Guide $guide = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:40'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url'],
            'cover' => ['nullable', 'image', 'max:3072'],
            'steps' => ['nullable', 'array'],
            'faqs' => ['nullable', 'array'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:200'],
            'read_minutes' => ['nullable', 'integer', 'min:1'],
            'is_published' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
        ]);

        $data['slug'] = $guide?->slug ?? Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');

        // Clean repeatable rows (drop empties).
        $data['steps'] = collect($request->input('steps', []))->filter(fn ($s) => ! empty($s['title']) || ! empty($s['body']))->values()->all();
        $data['faqs'] = collect($request->input('faqs', []))->filter(fn ($f) => ! empty($f['q']) || ! empty($f['a']))->values()->all();

        if ($request->hasFile('cover')) {
            $data['cover_image_path'] = $request->file('cover')->store('guides', 'public');
        }
        unset($data['cover']);

        return $data;
    }
}
