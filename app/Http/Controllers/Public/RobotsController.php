<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Seo\CanonicalUrlService;
use App\Services\Seo\SeoService;
use Illuminate\Http\Response;

/**
 * /robots.txt — dynamic and environment-aware, replacing the old static
 * public/robots.txt (which had to be deleted: a physical file at that path
 * is served directly by the web server and would silently mask this route
 * forever, so this is the ONLY robots.txt now — nothing to keep in sync).
 *
 * The admin panel's actual path is deliberately never listed here, disallowed
 * or otherwise — see brief section 14/26: robots.txt is a public, frequently
 * scraped file, and this app's admin panel is already reachable only via a
 * secret configurable URL prefix (config('platform.admin_path')) specifically
 * so that URL isn't guessable. Publishing "the admin panel lives at /xyz" in
 * a plaintext file everyone can read would defeat that on the spot. Real
 * protection is auth + that secret path, never this file — this only tidies
 * up crawl budget for known public/private route prefixes.
 */
class RobotsController extends Controller
{
    // Known authenticated/utility/private top-level path prefixes — crawl
    // budget hygiene only, not security (see class docblock). Review this
    // list when new top-level authenticated route groups are added; it is
    // not derived automatically from routes/web.php.
    private const DISALLOWED_PREFIXES = [
        '/dashboard', '/wallet', '/transactions', '/deposit', '/funding',
        '/security', '/profile', '/notifications', '/referrals', '/cart',
        '/login', '/register', '/two-factor-challenge', '/forgot-password',
        '/reset-password', '/reauth', '/verify-email', '/search',
        '/locale', '/region', '/onboard', '/p', '/files',
    ];

    public function __construct(private SeoService $seo, private CanonicalUrlService $canonical) {}

    public function show(): Response
    {
        $lines = ['User-agent: *'];

        if ($this->seo->isIndexingAllowed()) {
            foreach (self::DISALLOWED_PREFIXES as $prefix) {
                $lines[] = "Disallow: {$prefix}";
            }
        } else {
            // Non-production or indexing explicitly off — block everything,
            // the same environment-aware safeguard SeoService applies to
            // every page's own robots meta tag (see SeoService::isIndexingAllowed()).
            $lines[] = 'Disallow: /';
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.$this->canonical->normalize(route('sitemap.index'));

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }
}
