<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * One generic admin endpoint for every model that uses the reusable
 * seo_metadata table (see HasSeoMetadata) and is actually rendered through
 * SeoService::forModel() on a public page. Currently just Agent.
 *
 * ShopProduct also uses HasSeoMetadata (kept for when Zendit/Airalo product
 * data goes live) but is deliberately NOT routed through here yet: the
 * public product page never calls forModel() for it, so an admin SEO form
 * would save fields with no effect. Add it back to TYPES in the same PR
 * that wires SeoService::forModel() into ShopController::show().
 *
 * Page, Guide, and ShopCategory already have their own dedicated admin
 * forms for this (see SeoService::applyNativeSeoColumns()'s docblock), so
 * they're deliberately not routed through here either.
 *
 * A short type key in the URL (never the raw model class) keeps this from
 * becoming an arbitrary-class-lookup endpoint.
 */
class SeoMetadataController extends Controller
{
    private const TYPES = [
        'agent' => Agent::class,
    ];

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $modelClass = self::TYPES[$type];
        $model = $modelClass::findOrFail($id);

        $data = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'canonical_override' => ['nullable', 'string', 'max:255'],
            'robots' => ['nullable', 'string', Rule::in(['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])],
            'focus_topic' => ['nullable', 'string', 'max:255'],
            'sitemap_include' => ['nullable', 'boolean'],
        ]);
        // A real unchecked checkbox submits no field at all, so a missing
        // key means "off" here, not "on" — same convention as every other
        // checkbox toggle in this codebase (e.g. seo_indexing_enabled).
        $data['sitemap_include'] = $request->boolean('sitemap_include');

        $model->seoMetadata()->updateOrCreate([], array_merge($data, [
            'last_seo_review_at' => now(),
            'seo_reviewed_by' => Auth::id(),
        ]));

        app(AuditLogger::class)->log('admin.seo_metadata.updated', "SEO metadata updated for {$type} #{$id}");

        return back()->with('success', 'SEO details saved.');
    }
}
