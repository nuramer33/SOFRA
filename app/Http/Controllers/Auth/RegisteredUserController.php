<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Shop;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Sign-up creates the shop AND its owner together. The transaction means
     * we never end up with a shop without an owner (or the other way round).
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $shop = Shop::create([
                // Same name in every language to start; the owner can
                // translate it later in settings.
                'name' => array_fill_keys(Locales::codes(), $data['shop_name']),
                'slug' => $data['slug'],
                'brand_color' => config('sofra.defaults.brand_color'),
                'currency' => config('sofra.defaults.currency'),
                'template' => config('sofra.defaults.template'),
                'default_locale' => app()->getLocale(),
                'enabled_locales' => Locales::codes(),
            ]);
            $shop->startTrial(); // free period starts at sign-up
            $shop->save();

            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed by the model cast
                'locale' => app()->getLocale(),
            ]);
            $user->shop()->associate($shop);
            $user->save();

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', __('app.flash.welcome'));
    }
}
