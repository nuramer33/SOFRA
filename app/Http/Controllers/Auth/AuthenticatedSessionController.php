<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // New session id after login prevents session-fixation attacks.
        $request->session()->regenerate();

        return redirect()->intended($request->user()->homeUrl());
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Keep the language the visitor was using after logging out.
        $locale = app()->getLocale();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('locale', $locale);

        return redirect()->route('login');
    }
}
