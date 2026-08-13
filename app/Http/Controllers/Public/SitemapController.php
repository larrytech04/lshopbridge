<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Seo\CanonicalUrlService;
use App\Services\Seo\SitemapService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * /sitemap.xml (the index) and /sitemap-{group}.xml (one per content type —
 * see SitemapService::groups()). Every URL comes straight from the same
 * published/active/approved scopes the real public controllers use, so
 * nothing here can list a page that doesn't actually 200. Cached for an
 * hour — see class docblock on SitemapService for what "safely" means here:
 * a simple TTL, not per-model save-hook invalidation, which would touch
 * every content controller in the app for a page that already tolerates
 * being an hour stale.
 */
class SitemapController extends Controller
{
    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private SitemapService $sitemaps,
        private CanonicalUrlService $canonical,
    ) {}

    public function index(): Response
    {
        $xml = Cache::remember('sitemap.index', self::CACHE_TTL_SECONDS, function () {
            $sitemaps = collect($this->sitemaps->groups())
                ->keys()
                ->map(fn ($group) => [
                    'loc' => $this->canonical->normalize(route('sitemap.group', $group)),
                    'lastmod' => now()->toAtomString(),
                ]);

            return view('sitemap.index', ['sitemaps' => $sitemaps])->render();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function group(string $group): Response
    {
        abort_unless(array_key_exists($group, $this->sitemaps->groups()), 404);

        $xml = Cache::remember("sitemap.group.{$group}", self::CACHE_TTL_SECONDS, function () use ($group) {
            return view('sitemap.urlset', ['urls' => $this->sitemaps->urlsFor($group)])->render();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
