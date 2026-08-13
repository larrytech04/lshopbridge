{{--
    Reusable per-record SEO panel for any model using the generic
    seo_metadata table (see HasSeoMetadata / SeoMetadataController).
    Deliberately its own independent <form>, not folded into the model's
    main edit form — saving SEO details never risks the (often much more
    complex) primary save action, and vice versa.

    Included with ['model' => $record, 'type' => 'agent'] — $type must match
    a key in SeoMetadataController::TYPES.
--}}
@php $meta = $model->seoMetadata; @endphp

<x-glass-card>
    <h3 class="font-semibold text-strong">SEO</h3>
    <p class="mt-1 text-xs text-muted">Overrides the automatic title/description this page would otherwise use. Leave blank to keep the default.</p>

    <form method="POST" action="{{ route('admin.seo-metadata.update', ['type' => $type, 'id' => $model->id]) }}" class="mt-4 space-y-4">
        @csrf @method('PUT')

        <div>
            <label class="label">SEO title</label>
            <input name="meta_title" value="{{ old('meta_title', $meta?->meta_title) }}" maxlength="255" class="field" placeholder="Defaults to the page's own title">
        </div>
        <div>
            <label class="label">Meta description</label>
            <textarea name="meta_description" rows="2" maxlength="500" class="field">{{ old('meta_description', $meta?->meta_description) }}</textarea>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Canonical URL override</label>
                <input name="canonical_override" value="{{ old('canonical_override', $meta?->canonical_override) }}" maxlength="255" class="field" placeholder="/shop/p/example">
                <p class="mt-1 text-[11px] text-faint">Only set this if this exact content is also published somewhere else and this copy should point there instead.</p>
            </div>
            <div>
                <label class="label">Robots</label>
                <select name="robots" class="field">
                    <option value="" @selected(! $meta?->robots)>Default (index, follow)</option>
                    <option value="index,follow" @selected($meta?->robots === 'index,follow')>index, follow</option>
                    <option value="noindex,follow" @selected($meta?->robots === 'noindex,follow')>noindex, follow</option>
                    <option value="index,nofollow" @selected($meta?->robots === 'index,nofollow')>index, nofollow</option>
                    <option value="noindex,nofollow" @selected($meta?->robots === 'noindex,nofollow')>noindex, nofollow</option>
                </select>
            </div>
        </div>
        <div>
            <label class="label">Focus topic <span class="font-normal text-faint">(editorial note, not shown publicly)</span></label>
            <input name="focus_topic" value="{{ old('focus_topic', $meta?->focus_topic) }}" maxlength="255" class="field" placeholder="What is this page primarily about?">
        </div>
        <label class="flex items-center justify-between rounded-xl border border-app surface p-4">
            <span><span class="font-medium text-strong">Include in sitemap</span><br><span class="text-xs text-muted">Turn off to keep this page reachable but out of the sitemap.</span></span>
            <input type="checkbox" name="sitemap_include" value="1" @checked(old('sitemap_include', $meta?->sitemap_include ?? true)) class="h-5 w-9 rounded-full surface-2 text-brand-500">
        </label>

        @if ($meta?->last_seo_review_at)
            <p class="text-xs text-faint">Last reviewed {{ $meta->last_seo_review_at->diffForHumans() }}@if ($meta->reviewer) by {{ $meta->reviewer->name }}@endif.</p>
        @endif

        <button class="btn btn-primary">Save SEO details</button>
    </form>
</x-glass-card>
