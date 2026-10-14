<x-layouts.dashboard :title="__('account.title')">
    <x-page-header :title="__('account.title')" :subtitle="__('account.subtitle')" />

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('account.update') }}" class="reveal card space-y-5 p-6 sm:p-8" style="--i: 1">
            @csrf
            @method('PUT')
            <h2 class="font-display text-2xl font-semibold">{{ __('account.profile') }}</h2>

            <x-field name="name" :label="__('account.name')" :value="$user->name" autocomplete="name" required maxlength="80" />
            <x-field name="email" type="email" :label="__('account.email')" :value="$user->email" dir="ltr"
                     autocomplete="username" inputmode="email" required :hint="__('account.email_changed')" />

            <p class="flex items-center gap-2 text-sm {{ $user->hasVerifiedEmail() ? 'text-olive' : 'text-ink-mute' }}">
                @if ($user->hasVerifiedEmail())
                    <x-icon name="check" class="size-4" /> {{ __('account.verified_badge') }}
                @else
                    {{ __('account.verify_title') }}
                @endif
            </p>

            <button class="btn btn-primary">{{ __('account.save') }}</button>
        </form>

        <form method="POST" action="{{ route('account.password') }}" class="reveal card space-y-5 p-6 sm:p-8" style="--i: 2">
            @csrf
            @method('PUT')
            <h2 class="font-display text-2xl font-semibold">{{ __('account.password_section') }}</h2>

            <x-password-field name="current_password" :label="__('account.current_password')" autocomplete="current-password" />
            <x-password-field name="password" :label="__('account.new_password')" :hint="__('app.auth.password_hint')" autocomplete="new-password" />
            <x-password-field name="password_confirmation" :label="__('account.new_password_confirmation')" autocomplete="new-password" />

            <button class="btn btn-primary">{{ __('account.update_password') }}</button>
        </form>
    </div>
</x-layouts.dashboard>