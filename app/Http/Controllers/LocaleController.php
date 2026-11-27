<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The EN / BM switch. Kept for this browser, and saved on every account signed in here so later
 * sessions and emails use it too.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put('locale', $locale);

        // Quietly: a language switch is not worth an audit-trail entry.
        foreach (['web', 'admin'] as $guard) {
            Auth::guard($guard)->user()?->forceFill(['locale' => $locale])->saveQuietly();
        }

        return back();
    }
}
