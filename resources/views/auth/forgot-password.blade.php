<x-layouts.auth :title="__('app.auth.forgot_title')">
    <div class="reveal" style="--i: 1">
        <h1 class="font-display text-4xl font-semibold sm:text-5xl">{{ __('app.auth.forgot_title') }}</h1>
        <p class="mt-3 text-ink-soft">{{ __('app.auth.forgot_subtitle') }}</p>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl bg-olive-soft px-4 py-3 text-sm text-olive" role="status">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-9 space-y-5">
        @csrf
        <div class="reveal" style="--i: 2">
            <x-field name="email" type="email" :label="__('app.auth.email')" dir="ltr"
                     autocomplete="username" inputmode="email" required autofocus />
        </div>
        <div class="reveal pt-2" style="--i: 3">
            <button type="submit" class="btn btn-primary w-full">{{ __('app.auth.send_link_button') }}</button>
        </div>
    </form>

    <p class="reveal mt-8 text-center text-sm" style="--i: 4">
        <a href="{{ route('login') }}" class="font-medium text-clay underline-offset-4 hover:underline">{{ __('app.auth.back_to_login') }}</a>
    </p>
</x-layouts.auth>
