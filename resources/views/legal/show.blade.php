@php
    $content = __("legal.{$page}");
    $price = config('sofra.billing.monthly_price') ?: '$5';
    $whatsapp = config('sofra.billing.support_whatsapp');
    $email = config('sofra.billing.support_email');
@endphp
<x-layouts.base :title="$content['title']" body-class="grain">
    <x-slot:head>
        <x-seo :title="$content['title'].' · '.__('app.brand')" :description="$content['intro']" :url="route('legal.'.$page)" />
    </x-slot:head>

    <header class="mx-auto flex max-w-3xl items-center justify-between px-5 py-5 sm:px-8">
        <a href="{{ route('home') }}"><x-logo /></a>
        <x-locale-switcher />
    </header>

    <main class="mx-auto max-w-3xl px-5 pt-6 pb-20 sm:px-8">
        <article>
            <p class="eyebrow">{{ __('legal.updated_label') }}: {{ __('legal.updated') }}</p>
            <h1 class="mt-2 font-display text-4xl font-semibold sm:text-5xl">{{ $content['title'] }}</h1>
            <p class="mt-5 text-lg leading-relaxed text-ink-soft">{{ $content['intro'] }}</p>

            <div class="mt-10 space-y-9">
                @foreach ($content['sections'] as $i => $section)
                    <section>
                        <h2 class="font-display text-2xl font-semibold">{{ $i + 1 }}. {{ $section['title'] }}</h2>
                        <div class="mt-3 space-y-3 leading-relaxed text-ink-soft">
                            @foreach ($section['body'] as $paragraph)
                                <p>{{ str_replace(':price', $price, $paragraph) }}</p>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <section class="card mt-12 p-6">
                <h2 class="font-display text-2xl font-semibold">{{ __('legal.contact_title') }}</h2>
                <p class="mt-2 text-ink-soft">{{ __('legal.contact_text') }}</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($email)
                        <a href="mailto:{{ $email }}" class="btn btn-ghost" dir="ltr">{{ $email }}</a>
                    @endif
                    @if ($whatsapp)
                        <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-ghost" dir="ltr">WhatsApp</a>
                    @endif
                    @unless ($email || $whatsapp)
                        <p class="text-sm text-ink-mute">{{ __('legal.contact_none') }}</p>
                    @endunless
                </div>
            </section>
        </article>

        <nav class="mt-10 flex flex-wrap items-center justify-between gap-3 text-sm text-ink-soft">
            <a href="{{ route('home') }}" class="hover:text-clay">&larr; {{ __('app.errors.home') }}</a>
            <span class="flex gap-5">
                <a href="{{ route('legal.privacy') }}" class="hover:text-clay">{{ __('legal.footer.privacy') }}</a>
                <a href="{{ route('legal.terms') }}" class="hover:text-clay">{{ __('legal.footer.terms') }}</a>
            </span>
        </nav>
    </main>
</x-layouts.base>