<?php

namespace App\Http\Controllers;

use App\Support\Tutorial;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Tutorial page: how to use the parts of the app this person can open.
 */
class HelpController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('help.index', [
            'sections' => Tutorial::sectionsFor($request->user()),
        ]);
    }
}
