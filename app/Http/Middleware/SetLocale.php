<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's chosen UI language on every web request. The choice is
 * stored in the session (and on the user when logged in) by LocalizationController.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('platform.locales', ['en' => 'English']));

        $locale = session('locale')
            ?? optional($request->user())->locale
            ?? config('app.locale', 'en');

        if (! in_array($locale, $supported, true)) {
            $locale = 'en';
        }

        App::setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }
}
