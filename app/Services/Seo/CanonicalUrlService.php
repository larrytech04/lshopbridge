<?php

namespace App\Services\Seo;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Turns "the current request" or an admin-entered override into one
 * consistent, absolute, HTTPS canonical URL — the single place that decides
 * scheme, host, and which query parameters survive. Nothing else in the app
 * should build a canonical tag by hand (see the old `url()->current()` used
 * directly in layouts/public.blade.php before this existed — that emitted a
 * different "canonical" for every query-string variation of the same page).
 */
class CanonicalUrlService
{
    // Marketing/tracking params that never change what page is shown, so
    // they must never survive into a canonical URL — otherwise every ad
    // click or shared link mints a technically-distinct "canonical" for the
    // same content.
    private const STRIPPED_PARAMS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'fbclid', 'gclid', 'gclsrc', 'msclkid', 'ref', 'referrer', '_gl',
    ];

    /**
     * The canonical for the CURRENT request: forces https + the configured
     * app host, strips tracking params, keeps everything else (including
     * `page`, which legitimately identifies distinct paginated content and
     * must NOT collapse to page 1 — see brief section 7/15).
     */
    public function forCurrentRequest(Request $request): string
    {
        $query = collect($request->query())
            ->except(self::STRIPPED_PARAMS)
            ->all();

        $path = '/'.ltrim($request->path(), '/');
        $path = $path === '/' ? $path : rtrim($path, '/');

        return $this->normalize($this->buildUrl($path, $query));
    }

    /**
     * An admin-entered override — may be a full URL or an app-relative
     * path. Either way, it's forced through the same host/scheme
     * normalization so a pasted `http://` or `www.` link can't slip a
     * mismatched canonical into production.
     */
    public function fromOverride(string $override): string
    {
        if (Str::startsWith($override, ['http://', 'https://'])) {
            return $this->normalize($override);
        }

        return $this->normalize($this->buildUrl('/'.ltrim($override, '/')));
    }

    /** Force https + the configured app host on an already-built URL string. */
    public function normalize(string $url): string
    {
        $host = $this->canonicalHost();
        $parts = parse_url($url);

        // parse_url() only returns false on genuinely malformed input — a
        // bare root URL like "https://example.com" (no trailing slash) is
        // valid and simply has no 'path' key at all, not an empty one, so
        // that case must default to '/' rather than bailing out with the
        // un-normalized original (which used to leak the original http://
        // scheme straight past this method for exactly that URL shape).
        if ($parts === false) {
            return $url;
        }

        $path = empty($parts['path']) ? '/' : $parts['path'];
        // A caller passing a bare relative path (no leading slash — e.g.
        // forgetting to run a stored "branding/foo.jpg"-style value through
        // asset() first) would otherwise glue straight onto the host with
        // no separator. Caught this exact bug once already; this is the
        // backstop so the same mistake elsewhere can't repeat it.
        $path = str_starts_with($path, '/') ? $path : '/'.$path;
        $path = $path === '/' ? $path : rtrim($path, '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return "https://{$host}{$path}{$query}";
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $queryString = http_build_query($query);

        return $path.($queryString ? '?'.$queryString : '');
    }

    /** The one configured production host — never derived from whatever
     *  Host header a given request happened to arrive with. */
    private function canonicalHost(): string
    {
        $configured = setting('seo_canonical_domain') ?: config('app.url');

        return Str::of($configured)
            ->replace(['https://', 'http://'], '')
            ->rtrim('/')
            ->lower()
            ->toString();
    }
}
