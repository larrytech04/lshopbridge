<?php

namespace App\Services\Seo;

use App\Enums\CountryLaunchStatus;
use App\Models\Agent;
use App\Models\Country;
use App\Models\Guide;
use App\Models\Page;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Support\Collection;

/**
 * Builds the URL list for each sitemap group, straight from the same
 * published/active/approved scopes the real public controllers already use
 * to decide what's visible — never a separate "is this URL alive" crawl, so
 * a sitemap entry can't drift out of sync with what actually 200s.
 *
 * Static (non-model) pages are curated by hand below since there's no
 * table to query them from; keep that list in sync with routes/web.php's
 * public GET routes as they're added or removed.
 *
 * The shop-products and agents groups additionally respect a per-record
 * seo_metadata.sitemap_include = false override (see HasSeoMetadata) —
 * pages/guides/shop-categories have their own native SEO columns instead
 * (see SeoService::applyNativeSeoColumns()) with no such override field, so
 * nothing to check there beyond their normal published/active scope.
 */
class SitemapService
{
    // Hard protocol ceiling (sitemaps.org): 50,000 URLs per file. Nothing in
    // this app is anywhere near that yet — this just guarantees a future
    // content flood can never silently emit an invalid oversized file.
    private const MAX_URLS_PER_SITEMAP = 50000;

    public function __construct(private CanonicalUrlService $canonical) {}

    /** @return array<string, string> group key => human label, in sitemap-index order. */
    public function groups(): array
    {
        return [
            'pages' => 'Static & legal pages',
            'countries' => 'Country pages',
            'guides' => 'Learning Center guides',
            'shop-categories' => 'Shop categories',
            'shop-products' => 'Shop products',
            'agents' => 'Verified shipping agents',
        ];
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string}> */
    public function urlsFor(string $group): Collection
    {
        return match ($group) {
            'pages' => $this->staticPages()->merge($this->legalPages()),
            'countries' => $this->countries(),
            'guides' => $this->guides(),
            'shop-categories' => $this->shopCategories(),
            'shop-products' => $this->shopProducts(),
            'agents' => $this->agents(),
            default => collect(),
        };
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string}> */
    private function countries(): Collection
    {
        // Only countries that are both fully launched AND have real,
        // admin-configured payment infrastructure — see
        // Country::hasRealPaymentInfrastructure() and CountryController's
        // docblock. Every other "active" country (allowed to register from,
        // nothing more) stays reachable but noindex, not sitemapped.
        return Country::active()
            ->where('launch_status', CountryLaunchStatus::Active)
            ->limit(self::MAX_URLS_PER_SITEMAP)
            ->get()
            ->filter(fn (Country $country) => $country->hasRealPaymentInfrastructure())
            ->map(fn (Country $country) => [
                'loc' => $this->canonical->normalize(route('countries.show', $country)),
                'lastmod' => optional($country->updated_at)->toAtomString(),
            ])
            ->values();
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string}> */
    private function staticPages(): Collection
    {
        // No model backs these — hand-curated from routes/web.php's public,
        // canonical, content-bearing GET routes. Deliberately excludes:
        // auth/utility routes (login, register, cart, locale/region
        // switchers), the legacy /p/{slug} redirect-only route, and
        // anything requiring a query string to mean something.
        $routeNames = [
            'home', 'how-it-works', 'countries.index', 'public.fund', 'public.payment-methods',
            'public.fees', 'public.faqs', 'guides.index', 'agents.index',
            'contact', 'legal.index', 'shop.index', 'esim.index',
            'esim.compatibility.index',
        ];

        return collect($routeNames)
            ->filter(fn ($name) => \Illuminate\Support\Facades\Route::has($name))
            ->map(fn ($name) => [
                'loc' => $this->canonical->normalize(route($name)),
                'lastmod' => null,
            ])
            ->values();
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string}> */
    private function legalPages(): Collection
    {
        // No ->with('seoMetadata') here — Page has its own native meta_title/
        // meta_description columns (see SeoService::applyNativeSeoColumns()),
        // not the generic seo_metadata relation, and eager-loading a relation
        // that doesn't exist on the model throws (unlike lazily accessing it,
        // which Eloquent resolves to null harmlessly — see excludedFromSitemap()).
        return Page::query()->published()
            ->limit(self::MAX_URLS_PER_SITEMAP)
            ->get()
            ->reject(fn (Page $page) => $this->excludedFromSitemap($page))
            ->map(fn (Page $page) => [
                'loc' => $this->canonical->normalize(
                    route($page->type === 'legal' ? 'legal.show' : 'pages.show', $page)
                ),
                'lastmod' => optional($page->updated_at)->toAtomString(),
            ])
            ->values();
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string}> */
    private function guides(): Collection
    {
        // No ->with('seoMetadata') — same reason as legalPages() above; Guide
        // has its own native meta_title/meta_description columns.
        return Guide::query()->published()
            ->limit(self::MAX_URLS_PER_SITEMAP)
            ->get()
            ->reject(fn (Guide $guide) => $this->excludedFromSitemap($guide))
            ->map(fn (Guide $guide) => [
                'loc' => $this->canonical->normalize(route('guides.show', $guide)),
                'lastmod' => optional($guide->updated_at)->toAtomString(),
            ])
            ->values();
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string}> */
    private function shopCategories(): Collection
    {
        // No ->with('seoMetadata') — same reason as legalPages() above;
        // ShopCategory has its own native seo_title/meta_description/
        // canonical_url columns.
        return ShopCategory::query()->active()
            ->limit(self::MAX_URLS_PER_SITEMAP)
            ->get()
            ->reject(fn (ShopCategory $category) => $this->excludedFromSitemap($category))
            ->map(fn (ShopCategory $category) => [
                'loc' => $this->canonical->normalize(route('shop.category', $category)),
                'lastmod' => optional($category->updated_at)->toAtomString(),
            ])
            ->values();
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string}> */
    private function shopProducts(): Collection
    {
        return ShopProduct::query()->active()
            ->with('seoMetadata')
            ->limit(self::MAX_URLS_PER_SITEMAP)
            ->get()
            ->reject(fn (ShopProduct $product) => $this->excludedFromSitemap($product))
            ->map(fn (ShopProduct $product) => [
                'loc' => $this->canonical->normalize(route('shop.show', $product)),
                'lastmod' => optional($product->updated_at)->toAtomString(),
            ])
            ->values();
    }

    /** @return Collection<int, array{loc: string, lastmod: ?string}> */
    private function agents(): Collection
    {
        // Same gate the public profile itself enforces (AgentDirectoryController::show
        // 404s anything not approved) — a sitemap entry can never point at a
        // page that 404s.
        return Agent::query()->approved()
            ->with('seoMetadata')
            ->limit(self::MAX_URLS_PER_SITEMAP)
            ->get()
            ->reject(fn (Agent $agent) => $this->excludedFromSitemap($agent))
            ->map(fn (Agent $agent) => [
                'loc' => $this->canonical->normalize(route('agents.show', $agent)),
                'lastmod' => optional($agent->updated_at)->toAtomString(),
            ])
            ->values();
    }

    /** @param  \Illuminate\Database\Eloquent\Model&object{seoMetadata: mixed}  $model */
    private function excludedFromSitemap($model): bool
    {
        $meta = $model->seoMetadata;

        return $meta && ($meta->sitemap_include === false || str_starts_with((string) $meta->robots, 'noindex'));
    }
}
