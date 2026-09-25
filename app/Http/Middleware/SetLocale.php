<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Picks az or en from ?lang=, then Accept-Language, defaulting to Azerbaijani. */
class SetLocale
{
    public const SUPPORTED = ['az', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $lang = $request->query('lang');
        if (! in_array($lang, self::SUPPORTED, true)) {
            $lang = $request->getPreferredLanguage(self::SUPPORTED) ?? 'az';
        }
        app()->setLocale($lang);

        $response = $next($request);
        $response->headers->set('Content-Language', $lang);
        $response->setVary('Accept-Language', false);

        return $response;
    }
}
