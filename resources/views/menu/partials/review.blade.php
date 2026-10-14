{{-- "Rate us" dialog: stars + optional note, name, contact and photo. Reviews are private to the owner. --}}
<div x-data="reviewForm(@js(['url' => route('menu.review', $shop), 'lang' => app()->getLocale(), 'error' => __('app.review.error')]))"
     @open-review.window="show()" @keydown.escape.window="open && hide()"
     class="fixed inset-0 z-50 hidden" :class="{ 'hidden': !open }">
    <div class="absolute inset-0 bg-ink/50" @click="hide()"></div>

    <section role="dialog" aria-modal="true" aria-labelledby="review-title"
             class="absolute inset-x-0 bottom-0 mx-auto max-h-[94dvh] max-w-lg overflow-y-auto overscroll-contain rounded-t-[2rem] bg-paper px-6 pt-5 pb-[max(1.75rem,env(safe-area-inset-bottom))] shadow-2xl animate-[rise_.4s_var(--ease-soft)]">
        <button type="button" @click="hide()" aria-label="{{ __('app.menu.close') }}"
                class="absolute top-4 end-4 grid size-10 place-items-center rounded-full bg-sand text-ink transition active:scale-90">
            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>

        {{-- Thank you --}}
        <div x-show="done" class="py-8 text-center">
            <span class="mx-auto grid size-16 animate-[pop_.6s_var(--ease-spring)] place-items-center rounded-full bg-brand text-brand-fg">
                <x-icon name="check" class="size-8" />
            </span>
            <p class="mt-5 font-display text-3xl font-semibold">{{ __('app.review.thanks_title') }}</p>
            <p class="mt-2 text-ink-soft">{{ __('app.review.thanks_text') }}</p>
            <button type="button" @click="hide()" class="btn btn-ghost mt-8">{{ __('app.menu.close') }}</button>
        </div>

        <form x-show="!done" @submit.prevent="submit()" novalidate>
            <h2 id="review-title" class="pe-12 font-display text-3xl font-semibold">{{ __('app.review.title') }}</h2>
            <p class="mt-1 text-ink-soft">{{ __('app.review.subtitle') }}</p>

            {{-- Stars --}}
            <div class="mt-6 flex justify-center gap-1.5" dir="ltr" role="radiogroup" aria-label="{{ __('app.review.rating') }}" @mouseleave="hover = 0">
                @foreach (range(1, 5) as $n)
                    <button type="button" role="radio" :aria-checked="(rating === {{ $n }}).toString()"
                            aria-label="{{ __('app.review.stars', ['count' => $n]) }}"
                            @click="rating = {{ $n }}" @mouseenter="hover = {{ $n }}"
                            class="grid size-12 place-items-center rounded-full transition-transform duration-200 ease-(--ease-spring) active:scale-90"
                            :class="(hover || rating) >= {{ $n }} ? 'scale-110 text-saffron' : 'text-ink/15'">
                        <x-star class="size-10" />
                    </button>
                @endforeach
            </div>

            <div class="mt-6 space-y-4">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium">{{ __('app.review.comment') }}</span>
                    <textarea x-model="comment" rows="3" maxlength="1000" class="field" placeholder="{{ __('app.review.comment_placeholder') }}"></textarea>
                </label>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium">{{ __('app.review.name') }}</span>
                        <input x-model="name" type="text" maxlength="60" autocomplete="name" class="field" placeholder="{{ __('app.review.name_placeholder') }}">
                    </label>
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium">{{ __('app.review.contact') }}</span>
                        <input x-model="contact" type="text" maxlength="80" autocomplete="email" dir="auto" class="field" placeholder="{{ __('app.review.contact_placeholder') }}">
                    </label>
                </div>

                {{-- Honeypot: invisible to people, tempting to bots --}}
                <input x-model="website" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="absolute -start-[9999px] h-0 w-0 opacity-0">

                {{-- Photo --}}
                <div>
                    <input x-ref="file" type="file" accept="image/*" class="sr-only" @change="pick($event)">
                    <template x-if="!preview">
                        <button type="button" @click="$refs.file.click()"
                                class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-line bg-white/50 px-4 py-4 text-ink-soft transition hover:border-brand hover:text-ink">
                            <x-icon name="plus" class="size-4" /> {{ __('app.review.add_photo') }}
                        </button>
                    </template>
                    <template x-if="preview">
                        <div class="flex items-center gap-4 rounded-2xl bg-white/60 p-2 ring-1 ring-line">
                            <img :src="preview" alt="" class="size-20 rounded-xl object-cover">
                            <button type="button" @click="clearPhoto()" class="text-sm text-ink-mute underline-offset-4 hover:text-danger hover:underline">{{ __('app.review.remove_photo') }}</button>
                        </div>
                    </template>
                </div>
            </div>

            <p x-show="error" x-text="error" class="mt-4 text-sm text-danger" role="alert"></p>

            <button type="submit" :disabled="!rating || busy"
                    class="btn mt-6 w-full bg-brand text-brand-fg shadow-[0_10px_24px_-12px_var(--brand)] hover:brightness-110 disabled:opacity-40">
                <span x-text="busy ? @js(__('app.review.sending')) : @js(__('app.review.send'))"></span>
            </button>
        </form>
    </section>
</div>