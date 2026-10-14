{{-- Reminder for owners who haven't confirmed their email yet. --}}
@auth
    @unless (auth()->user()->hasVerifiedEmail())
        <section {{ $attributes->class('reveal mb-6 flex flex-col gap-3 rounded-3xl bg-saffron/15 p-5 ring-1 ring-saffron/40 sm:flex-row sm:items-center') }}>
            <div class="flex-1">
                <h2 class="font-display text-lg font-semibold">{{ __('account.verify_title') }}</h2>
                <p class="mt-1 text-sm text-ink-soft">{{ __('account.verify_text', ['email' => auth()->user()->email]) }}</p>
            </div>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="btn btn-ghost shrink-0 text-sm">{{ __('account.verify_resend') }}</button>
            </form>
        </section>
    @endunless
@endauth