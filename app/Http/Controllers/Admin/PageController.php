<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageRevision;
use App\Services\Admin\PageAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private PageAdminService $service) {}

    public function index(): View
    {
        return view('admin.pages.index', ['pages' => Page::withTrashed()->orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page]);
    }

    public function store(Request $request)
    {
        $this->service->create($this->validated($request), $request->user());

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', ['page' => $page, 'revisions' => $page->revisions]);
    }

    public function update(Request $request, Page $page)
    {
        $this->service->update($page, $this->validated($request, $page), $request->user());

        return redirect()->route('admin.pages.index')->with('success', 'Page updated (v'.$page->fresh()->version.').');
    }

    public function restoreRevision(Request $request, Page $page, PageRevision $revision)
    {
        $this->service->restoreRevision($page, $revision, $request->user());

        return redirect()->route('admin.pages.edit', $page)->with('success', "Restored v{$revision->version}.");
    }

    /** Archive-not-delete: soft-deletes so a legal/info page's history and any external links aren't destroyed. */
    public function destroy(Request $request, Page $page)
    {
        $this->service->archive($page, $request->user());

        return back()->with('success', 'Page archived.');
    }

    public function restore(Request $request, Page $page)
    {
        $this->service->restore($page, $request->user());

        return back()->with('success', 'Page restored.');
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        // Form sends these as comma-separated text (simplest input for an
        // admin-only field with no fixed option list) — split into arrays
        // before validating/storing so Page's `array` casts apply cleanly.
        foreach (['applicable_services', 'applicable_countries'] as $listField) {
            $raw = (string) $request->input($listField, '');
            $request->merge([
                $listField => collect(explode(',', $raw))
                    ->map(fn ($v) => trim($v))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            // Slug is only accepted (and required) on create — the edit form disables the field
            // and the service also strips it, but validation is tightened here too.
            'slug' => [$page ? 'prohibited' : 'nullable', 'string', 'max:160'],
            'type' => ['required', 'in:legal,info'],
            'category' => ['nullable', 'in:'.implode(',', array_keys(\App\Models\Page::CATEGORIES))],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'plain_summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'applicable_services' => ['nullable', 'array'],
            'applicable_services.*' => ['string', 'max:60'],
            'applicable_countries' => ['nullable', 'array'],
            'applicable_countries.*' => ['string', 'size:2'],
            'effective_date' => ['nullable', 'date'],
            'internal_review_notes' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
