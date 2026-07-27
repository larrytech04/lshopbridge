@extends('layouts.admin')
@section('page-title', $page->exists ? 'Edit page' : 'New page')

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <a href="{{ route('admin.pages.index') }}" class="text-sm text-brand-600 hover:text-brand-700">← Pages</a>

    <x-glass-card>
        <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="space-y-4">
            @csrf @if($page->exists)@method('PUT')@endif
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Title</label><input name="title" value="{{ old('title', $page->title) }}" required class="field"></div>
                <div>
                    <label class="label">Slug @if($page->exists)<span class="text-faint">(locked)</span>@endif</label>
                    <input name="slug" value="{{ old('slug', $page->slug) }}" @disabled($page->exists) class="field" placeholder="terms, privacy, refund-policy, about">
                    @if ($page->exists)<p class="mt-1 text-[11px] text-faint">The slug backs a public URL (/p/{{ $page->slug }}) and can't be changed here to avoid breaking links.</p>@endif
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label">Type</label><select name="type" x-data x-on:change="$el.closest('form').querySelector('[data-legal-only]').style.display = $el.value === 'legal' ? '' : 'none'" class="field"><option value="legal" @selected(($page->type ?? 'legal')==='legal')>Legal</option><option value="info" @selected(($page->type ?? '')==='info')>Info</option></select></div>
                <div data-legal-only style="display: {{ ($page->type ?? 'legal') === 'legal' ? '' : 'none' }}">
                    <label class="label">Category</label>
                    <select name="category" class="field">
                        <option value="">— None —</option>
                        @foreach (\App\Models\Page::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" @selected(old('category', $page->category) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-faint">Groups this document on the /legal hub. Only used when type is Legal.</p>
                </div>
            </div>
            <div><label class="label">Excerpt</label><textarea name="excerpt" rows="2" class="field" placeholder="Shown under the title, and as the page's search-result description. @{{token}} placeholders like @{{site_name}} are supported.">{{ old('excerpt', $page->excerpt) }}</textarea></div>
            <div>
                <label class="label">Plain-English summary</label>
                <textarea name="plain_summary" rows="5" class="field" placeholder="Markdown bullet list — the &quot;In simple terms&quot; box shown above the full policy. @{{token}} placeholders supported.">{{ old('plain_summary', $page->plain_summary) }}</textarea>
                <p class="mt-1 text-[11px] text-faint">Optional, but every published legal document should have one — it's what makes the policy approachable to an ordinary customer.</p>
            </div>
            <div>
                <label class="label">Body (Markdown)</label>
                <textarea name="body" rows="20" class="field font-mono text-xs" placeholder="## Section Heading&#10;&#10;Body text. Use ## for major sections, ### for subsections — both become table-of-contents entries and deep-link anchors automatically.">{{ old('body', $page->body) }}</textarea>
                <p class="mt-1 text-[11px] text-faint">Markdown only — raw HTML is always escaped, never executed. Use @{{company_legal_name}}, @{{company_registered_address}}, @{{company_jurisdiction}}, @{{legal_email}}, @{{privacy_email}}, @{{compliance_email}}, @{{support_email}}, @{{support_phone}}, @{{site_name}}, or @{{company_trading_name}} — these resolve from Settings → Legal & Company, and show a clearly-bracketed placeholder until set.</p>
            </div>

            <div class="grid gap-4 border-t border-app pt-4 sm:grid-cols-2">
                <div>
                    <label class="label">Applicable services (optional)</label>
                    <input name="applicable_services" value="{{ old('applicable_services', is_array($page->applicable_services ?? null) ? implode(', ', $page->applicable_services) : '') }}" class="field" placeholder="e.g. withdrawals, shipping_agents">
                    <p class="mt-1 text-[11px] text-faint">Comma-separated. Leave blank to show this policy regardless of which services are active. Only hides the page when none of the listed services exist on this install.</p>
                </div>
                <div>
                    <label class="label">Applicable countries (optional)</label>
                    <input name="applicable_countries" value="{{ old('applicable_countries', is_array($page->applicable_countries ?? null) ? implode(', ', $page->applicable_countries) : '') }}" class="field" placeholder="e.g. CM, NG, GH">
                    <p class="mt-1 text-[11px] text-faint">Comma-separated ISO country codes. Leave blank for "all supported countries."</p>
                </div>
                <div><label class="label">Effective date</label><input type="date" name="effective_date" value="{{ old('effective_date', $page->effective_date?->format('Y-m-d')) }}" class="field"></div>
            </div>

            <div class="border-t border-app pt-4">
                <label class="label">Internal review notes <span class="text-faint">(admin-only — never shown publicly)</span></label>
                <textarea name="internal_review_notes" rows="3" class="field" placeholder="e.g. Governing law not confirmed. Refund period requires verification.">{{ old('internal_review_notes', $page->internal_review_notes) }}</textarea>
            </div>

            <div class="border-t border-app pt-4">
                <p class="label mb-2">SEO</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="label text-xs">Meta title (optional)</label><input name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" class="field" placeholder="{{ $page->title ?: 'Defaults to the page title' }}"></div>
                    <div><label class="label text-xs">Meta description (optional)</label><input name="meta_description" value="{{ old('meta_description', $page->meta_description) }}" class="field" placeholder="Defaults to the excerpt"></div>
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published ?? true)) class="rounded surface-2"> Published</label>
            <button class="btn btn-primary">{{ $page->exists ? 'Save as v'.($page->version + 1) : 'Create' }} page</button>
        </form>
    </x-glass-card>

    @if ($page->exists && isset($revisions) && $revisions->isNotEmpty())
        <x-glass-card>
            <h3 class="font-semibold text-strong">Version history</h3>
            <p class="text-xs text-faint">Currently v{{ $page->version }}. Publishing a change never overwrites a prior version — it's always saved here first.</p>
            <div class="mt-3 divide-y divide-app">
                @foreach ($revisions as $rev)
                    <div class="flex items-center justify-between gap-3 py-3">
                        <div>
                            <p class="text-sm font-medium text-strong">v{{ $rev->version }} · {{ $rev->title }}</p>
                            <p class="text-xs text-faint">{{ $rev->created_at->format('M j, Y g:i A') }} @if($rev->editor) · {{ $rev->editor->name }} @endif · {{ $rev->is_published ? 'Published' : 'Draft' }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.pages.revisions.restore', [$page, $rev]) }}" onsubmit="return confirm('Restore v{{ $rev->version }}? This will be saved as a new version.')">
                            @csrf<button class="text-xs text-brand-600">Restore this version</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </x-glass-card>
    @endif
</div>
@endsection
