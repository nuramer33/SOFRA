<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch the interface language. The route only accepts supported codes.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put('locale', $locale);

        // Logged-in owners keep their choice across devices.
        $request->user()?->update(['locale' => $locale]);

        return redirect()->back(fallback: route('home'));
    }
}
