<x-layouts.auth :title="__('app.auth.login_title')">
    <div class="reveal" style="--i: 1">
        <h1 class="font-display text-4xl font-semibold sm:text-5xl">{{ __('app.auth.login_title') }}</h1>
        <p class="mt-3 text-ink-soft">{{ __('app.auth.login_subtitle') }}</p>
    </div>

    @if (session('status'))
        <p class="reveal mt-6 rounded-2xl bg-olive-soft px-4 py-3 text-sm text-olive" role="status">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-9 space-y-5" x-data="{ busy: false }" @submit="busy = true">
        @csrf

        <div class="reveal" style="--i: 2">
            <x-field name="email" type="email" :label="__('app.auth.email')" dir="ltr"
                     autocomplete="username" inputmode="email" required autofocus />
        </div>

        <div class="reveal" style="--i: 3">
            <x-password-field :label="__('app.auth.password')">
                <x-slot:aside>
                    <a href="{{ route('password.request') }}" class="text-sm text-clay underline-offset-4 hover:underline">{{ __('app.auth.forgot_link') }}</a>
                </x-slot:aside>
            </x-password-field>
        </div>

        <label class="reveal flex w-fit cursor-pointer items-center gap-3 text-sm text-ink-soft" style="--i: 4">
            <input type="checkbox" name="remember" class="size-5 rounded-md border-line accent-clay">
            {{ __('app.auth.remember') }}
        </label>

        <div class="reveal pt-2" style="--i: 5">
            <button type="submit" class="btn btn-primary group w-full" :disabled="busy">
                {{ __('app.auth.login_button') }}
                <x-icon name="arrow" class="flip-rtl size-4 transition-transform duration-300 group-hover:translate-x-1 rtl:group-hover:-translate-x-1" />
            </button>
        </div>
    </form>

    <p class="reveal mt-8 text-center text-sm text-ink-soft" style="--i: 6">
        {{ __('app.auth.no_account') }}
        <a href="{{ route('register') }}" class="font-medium text-clay underline-offset-4 hover:underline">{{ __('app.auth.create_account') }}</a>
    </p>
</x-layouts.auth>
