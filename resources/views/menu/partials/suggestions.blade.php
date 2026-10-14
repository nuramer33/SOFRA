{{--
    "Goes well with" cards. Optional for the customer: add one tap, or dismiss.
    Expects $list: the name of the Alpine getter that returns the suggestions.
    A discount badge appears only when the owner set a valid discount.
--}}
<section x-show="{{ $list }}.length" x-cloak class="mt-6 rounded-[1.5rem] bg-sand/60 p-4 ring-1 ring-line/70" aria-label="{{ __('suggestions.menu.heading') }}">
    <h3 class="px-1 pb-2 font-display text-lg font-semibold">{{ __('suggestions.menu.heading') }}</h3>
    <ul class="space-y-2">
        <template x-for="s in {{ $list }}" :key="s.key">
            <li class="flex items-center gap-3 rounded-2xl bg-paper p-2.5 ring-1 ring-line/70">
                <span class="arch grid h-14 w-12 shrink-0 place-items-center overflow-hidden bg-sand">
                    <template x-if="s.thumb"><img :src="s.thumb" alt="" loading="lazy" class="size-full object-cover"></template>
                    <template x-if="!s.thumb"><span class="font-display text-lg text-ink-mute" x-text="s.name.charAt(0)"></span></template>
                </span>
                <span class="min-w-0 flex-1">
                    <span x-show="s.title" class="block truncate text-xs text-ink-mute" x-text="s.title"></span>
                    <span class="block truncate font-medium" x-text="s.name"></span>
                    <span x-show="s.text" class="block text-xs text-ink-soft" x-text="s.text"></span>
                    <span class="mt-0.5 flex flex-wrap items-baseline gap-x-2 gap-y-0.5 text-sm">
                        <bdi class="font-semibold text-brand-ink tabular-nums" x-text="money(s.final)"></bdi>
                        <bdi x-show="s.label" class="text-xs text-ink-mute tabular-nums line-through" x-text="money(s.price)"></bdi>
                        <span x-show="s.label" class="rounded-full bg-saffron/25 px-2 py-0.5 text-[11px] font-medium text-clay-deep" x-text="s.label"></span>
                    </span>
                    <span x-show="s.condition" class="block text-[11px] text-ink-mute" x-text="s.condition"></span>
                </span>
                <span class="flex shrink-0 flex-col items-stretch gap-1">
                    <button type="button" @click="acceptSuggestion(s)"
                            class="inline-flex min-h-10 items-center justify-center rounded-full bg-brand px-4 text-sm font-semibold text-brand-fg transition active:scale-95">
                        {{ __('suggestions.menu.add') }}
                    </button>
                    <button type="button" @click="dismissSuggestion(s)"
                            class="inline-flex min-h-8 items-center justify-center rounded-full px-2 text-xs text-ink-mute transition hover:text-ink">
                        {{ __('suggestions.menu.dismiss') }}
                    </button>
                </span>
            </li>
        </template>
    </ul>
</section>
