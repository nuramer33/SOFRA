<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']], [], ['email' => __('app.auth.email')]);

        $status = Password::sendResetLink($request->only('email'));

        // Always answer with the same "sent" message, so this form can't be
        // used to discover which emails have an account. Throttling still
        // reports its own message (it doesn't reveal anything).
        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors(['email' => __($status)]);
        }

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
