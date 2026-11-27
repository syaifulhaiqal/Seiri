<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use the language picked with the EN / BM switch, then the signed-in user's saved one,
 * then the app default.
 */
class SetLocale
{
    /**
     * The languages the app is translated into.
     */
    public const array LOCALES = ['en' => 'English', 'ms' => 'Bahasa Melayu'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale')
            ?? Auth::guard($request->area() === 'admin.' ? 'admin' : 'web')->user()?->locale;

        if (array_key_exists((string) $locale, self::LOCALES)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
