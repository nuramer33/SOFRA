<x-layouts.auth :title="__('app.auth.reset_title')">
    <div class="reveal" style="--i: 1">
        <h1 class="font-display text-4xl font-semibold sm:text-5xl">{{ __('app.auth.reset_title') }}</h1>
        <p class="mt-3 text-ink-soft">{{ __('app.auth.reset_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="mt-9 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="reveal" style="--i: 2">
            <x-field name="email" type="email" :label="__('app.auth.email')" dir="ltr"
                     :value="$request->email" autocomplete="username" required />
        </div>
        <div class="reveal" style="--i: 3">
            <x-password-field :label="__('app.auth.password')" :hint="__('app.auth.password_hint')" autocomplete="new-password" />
        </div>
        <div class="reveal" style="--i: 4">
            <x-password-field name="password_confirmation" :label="__('app.auth.password_confirmation')" autocomplete="new-password" />
        </div>
        <div class="reveal pt-2" style="--i: 5">
            <button type="submit" class="btn btn-primary w-full">{{ __('app.auth.reset_button') }}</button>
        </div>
    </form>
</x-layouts.auth>
