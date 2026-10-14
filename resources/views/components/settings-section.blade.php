{{-- A settings group: title + explanation beside (desktop) or above (mobile) the fields. --}}
@props(['title', 'text', 'i' => 0])
<section {{ $attributes->class('reveal card grid gap-6 p-5 sm:p-7 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-10') }} style="--i: {{ $i }}">
    <div>
        <h2 class="font-display text-xl font-semibold">{{ $title }}</h2>
        <p class="mt-1 text-sm text-ink-soft">{{ $text }}</p>
    </div>
    <div class="min-w-0 space-y-6">
        {{ $slot }}
    </div>
</section>
