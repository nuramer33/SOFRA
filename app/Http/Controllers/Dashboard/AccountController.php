<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AccountRequest;
use App\Http\Requests\Dashboard\PasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The signed-in owner's own login details (not the shop's settings).
 */
class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('dashboard.account', ['user' => $request->user()]);
    }

    public function update(AccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        // A new address has to be confirmed again.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()->route('account.edit')->with('status', __('account.saved'));
    }

    public function password(PasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->validated('password')]);

        return redirect()->route('account.edit')->with('status', __('account.password_updated'));
    }
}
