<?php

namespace App\Services\Seo;

use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * The single place that decides what a page's <head> actually contains:
 * site-wide defaults, layered with a stored SeoMetadata row (if the model
 * uses HasSeoMetadata and has one), layered with explicit per-request
 * overrides from the controller/view — in that precedence order, each
 * later layer winning. See SeoData for the shape, CanonicalUrlService for
 * URL normalization, StructuredDataBuilder for JSON-LD.
 *
 * Organization/WebSite JSON-LD are deliberately NOT added automatically to
 * every page here — call organizationBlock()/websiteBlock() explicitly
 * where the brief calls for them (the homepage, About) rather than
 * repeating the same entity block on every single page for no benefit.
 */
class SeoService
{
    public function __construct(
        private CanonicalUrlService $canonical,
        private StructuredDataBuilder $schema,
    ) {}

    /** Site-wide defaults for the current request — the base every page builds on. */
    public function defaults(Request $request): SeoData
    {
        $siteName = (string) setting('site_name', config('platform.name'));
        $tagline = (string) config('platform.tagline');

        return new SeoData(
            title: trim("{$tagline} | {$siteName}"),
            description: $tagline,
            canonical: $this->canonical->forCurrentRequest($request),
            robots: $this->defaultRobots(),
            ogImage: $this->defaultOgImage(),
            twitterSite: setting('seo_twitter_handle') ?: null,
        );
    }

    /**
     * Build SEO for a specific model: defaults, then that model's own
     * per-record SEO source (whichever one it actually has — see below),
     * then $overrides (highest precedence — what the controller/view
     * explicitly passed for THIS request/render).
     */
    public function forModel(Request $request, Model $model, array $overrides = []): SeoData
    {
        $seo = $this->defaults($request);

        $seo = method_exists($model, 'seoMetadata')
            ? $this->applySeoMetadataRow($seo, $model)
            : $this->applyNativeSeoColumns($seo, $model);

        $seo = $overrides === [] ? $seo : $seo->with($overrides);

        return $this->enforceIndexingSafeguard($seo);
    }

    /** Page/Guide/ShopCategory already shipped their own dedicated
     *  meta_title (or seo_title)/meta_description(/canonical_url) columns —
     *  with a real admin form already wired to them — before this SEO
     *  system existed. Reading those directly instead of introducing a
     *  second, parallel storage location for the exact same thing.
     *  getAttribute() returns null harmlessly for a column a given model
     *  doesn't have, so trying both naming variants is safe. */
    private function applyNativeSeoColumns(SeoData $seo, Model $model): SeoData
    {
        $canonicalUrl = $model->getAttribute('canonical_url');

        return $seo->with([
            'title' => $model->getAttribute('meta_title') ?? $model->getAttribute('seo_title'),
            'description' => $model->getAttribute('meta_description'),
            'canonical' => $canonicalUrl ? $this->canonical->fromOverride($canonicalUrl) : null,
        ]);
    }

    /** ShopProduct/Agent (and any future model with no native SEO columns
     *  of their own) use the generic seo_metadata table via HasSeoMetadata. */
    private function applySeoMetadataRow(SeoData $seo, Model $model): SeoData
    {
        $meta = $model->seoMetadata;

        if (! $meta instanceof SeoMetadata) {
            return $seo;
        }

        return $seo->with([
            'title' => $meta->meta_title,
            'description' => $meta->meta_description,
            'canonical' => $meta->canonical_override
                ? $this->canonical->fromOverride($meta->canonical_override)
                : null,
            'robots' => $meta->robots,
            'ogTitle' => $meta->og_title,
            'ogDescription' => $meta->og_description,
            // Same storage-path-to-URL convention as site_logo() — an
            // admin-uploaded path, turned into a full URL and normalized to
            // the canonical scheme/host.
            'ogImage' => $meta->og_image_path
                ? $this->canonical->normalize(asset($meta->og_image_path))
                : null,
        ]);
    }

    /** Build SEO for a page with no backing model — pass explicit overrides directly. */
    public function build(Request $request, array $overrides = []): SeoData
    {
        $seo = $this->defaults($request);
        $seo = $overrides === [] ? $seo : $seo->with($overrides);

        return $this->enforceIndexingSafeguard($seo);
    }

    /**
     * The LAST word on robots, always — no per-model column, admin-entered
     * seo_metadata.robots value, or page-level @section('robots', ...)
     * override is ever allowed to force indexing when the environment
     * safeguard says no (see isIndexingAllowed()). A page CAN always ask
     * for MORE restriction (noindex on an otherwise-indexable page); it can
     * never ask for less than the environment allows.
     */
    private function enforceIndexingSafeguard(SeoData $seo): SeoData
    {
        if ($this->isIndexingAllowed() || str_starts_with($seo->robots, 'noindex')) {
            return $seo;
        }

        return $seo->with(['robots' => 'noindex,nofollow']);
    }

    /** Appends one more JSON-LD block without disturbing whatever's already on $seo. */
    public function appendStructuredData(SeoData $seo, array $block): SeoData
    {
        return $seo->with(['structuredData' => [...$seo->structuredData, $block]]);
    }

    /** Sets breadcrumb data AND appends the matching BreadcrumbList JSON-LD in one call. */
    public function withBreadcrumbs(SeoData $seo, array $items): SeoData
    {
        return $this->appendStructuredData($seo->with(['breadcrumbs' => $items]), $this->schema->breadcrumbList($items));
    }

    /** Organization JSON-LD from real, admin-configured company data — see
     *  brief section 11/22. Never fabricates a name/logo/contact that isn't
     *  actually configured; those keys are simply omitted when unset. */
    public function organizationBlock(): array
    {
        return $this->schema->organization([
            'name' => setting('company_trading_name') ?: setting('site_name', config('platform.name')),
            'url' => $this->canonical->normalize(config('app.url').'/'),
            'logo' => $this->canonical->normalize(site_logo()),
            'description' => config('platform.tagline'),
            'sameAs' => array_values(array_filter([
                setting('social_facebook'),
                setting('social_x'),
                setting('social_instagram'),
                setting('social_tiktok'),
                setting('social_discord'),
            ])),
            'contactEmail' => setting('support_email'),
            'contactPhone' => setting('social_phone'),
        ]);
    }

    public function websiteBlock(): array
    {
        return $this->schema->website([
            'name' => (string) setting('site_name', config('platform.name')),
            'url' => $this->canonical->normalize(config('app.url').'/'),
        ]);
    }

    /**
     * True only when this environment AND the admin setting both explicitly
     * allow indexing — the environment-aware safeguard from brief section
     * 14. Defaults to "off" everywhere except a real production
     * environment, so a staging/preview deploy is never accidentally
     * indexable just because nobody remembered to flip a setting there.
     */
    public function isIndexingAllowed(): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        return (bool) setting('seo_indexing_enabled', true);
    }

    private function defaultRobots(): string
    {
        return $this->isIndexingAllowed() ? 'index,follow' : 'noindex,nofollow';
    }

    private function defaultOgImage(): ?string
    {
        // seo_default_og_image is stored the same way as site_logo_path —
        // a relative path under public/, not a full URL — so it needs the
        // same asset() conversion before it's a real URL to normalize.
        // Skipping that would silently glue the host and path together
        // with no separating slash (a real bug caught while building this).
        $image = ($path = setting('seo_default_og_image')) ? asset($path) : site_logo();

        // site_logo()/asset() reflect whatever scheme the current request
        // actually arrived on — forced through the same https+canonical-host
        // normalization as everything else here, so a share image never
        // ends up on a different scheme/host than the page linking to it.
        return $image ? $this->canonical->normalize($image) : null;
    }
}
