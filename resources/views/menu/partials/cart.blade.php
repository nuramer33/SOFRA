{{-- Floating cart bar + cart sheet. Included only when the shop accepts orders. --}}
@php
    $types = $shop->orderTypes();
    $inputClass = 'field py-3';
@endphp

{{-- Cart bar: slides up once something is in the order --}}
<div class="pointer-events-none fixed inset-x-0 bottom-0 z-40 px-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
    <button type="button" x-ref="cartBar" @click="openCart($el)"
            class="pointer-events-auto mx-auto flex w-full max-w-md items-center gap-3 rounded-full bg-ink py-2.5 ps-2.5 pe-5 text-paper shadow-[0_18px_40px_-16px_rgb(36_26_20/0.65)] transition-[translate,opacity] duration-500 ease-(--ease-spring)"
            :class="$store.cart.count && !sheet ? 'translate-y-0 opacity-100' : 'pointer-events-none translate-y-[150%] opacity-0'"
            :aria-hidden="(!$store.cart.count).toString()" :tabindex="$store.cart.count ? 0 : -1">
        <span class="grid size-10 place-items-center rounded-full bg-brand font-semibold text-brand-fg tabular-nums" x-text="$store.cart.count"></span>
        <span class="font-medium">{{ __('app.cart.view') }}</span>
        <bdi class="ms-auto font-semibold tabular-nums" x-text="money(cartTotal)"></bdi>
    </button>
</div>

{{-- Cart sheet --}}
<div class="fixed inset-0 z-50 hidden" :class="{ 'hidden': sheet !== 'cart', 'is-closing': closing && sheet === 'cart' }"
     @keydown.escape.window="sheet === 'cart' && close()">
    <div class="sheet-backdrop absolute inset-0 bg-ink/50" @click="close()"></div>

    <section role="dialog" aria-modal="true" aria-labelledby="cart-title"
             class="sheet-panel absolute inset-x-0 bottom-0 mx-auto flex max-h-[94dvh] max-w-2xl flex-col overflow-y-auto overscroll-contain rounded-t-[2rem] bg-paper shadow-2xl"
             :class="dragY ? '' : 'transition-transform duration-300 ease-(--ease-soft)'"
             :style="dragY && sheet === 'cart' ? `transform: translateY(${dragY}px)` : ''">
        <div class="sticky top-0 z-10 bg-paper/95 backdrop-blur" @pointerdown="dragStart($event)">
            <div class="flex cursor-grab touch-none justify-center pt-3 pb-1"><span class="h-1.5 w-12 rounded-full bg-ink/20"></span></div>
            <div class="flex items-center justify-between px-6 pb-3">
                <h2 id="cart-title" class="font-display text-2xl font-semibold">{{ __('app.cart.title') }}</h2>
                <button type="button" x-ref="cartClose" @click="close()"
                        class="grid size-10 place-items-center rounded-full bg-sand text-ink transition active:scale-90"
                        aria-label="{{ __('app.menu.close') }}">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
        </div>

        {{-- Sent ------------------------------------------------------- --}}
        <template x-if="sent && sent.demo">
            {{-- Demo shop: show the message the owner would receive --}}
            <div class="px-6 pt-4 pb-[max(2.5rem,env(safe-area-inset-bottom))]">
                <p class="text-center font-display text-2xl font-semibold">{{ __('app.cart.demo_title') }}</p>
                <p class="mx-auto mt-2 max-w-sm text-center text-sm text-ink-soft">{{ __('app.cart.demo_text') }}</p>
                <div class="mt-6 rounded-[1.75rem] bg-[#efeae2] p-4">
                    <div class="ms-auto max-w-[92%] animate-[pop_.5s_var(--ease-spring)] rounded-2xl rounded-se-sm bg-[#d9fdd3] p-4 text-[15px] text-[#111b21] leading-relaxed whitespace-pre-line shadow-sm"
                         x-text="sent.message"></div>
                </div>
                <div class="mt-6 flex flex-col gap-2">
                    <a href="{{ route('register') }}" class="btn btn-primary">{{ __('app.cart.demo_cta') }}</a>
                    <button type="button" @click="close()" class="btn btn-ghost">{{ __('app.cart.new_order') }}</button>
                </div>
            </div>
        </template>

        <template x-if="sent && !sent.demo">
            <div class="px-6 pt-4 pb-[max(2.5rem,env(safe-area-inset-bottom))] text-center">
                <span class="mx-auto grid size-16 animate-[pop_.6s_var(--ease-spring)] place-items-center rounded-full bg-[#0f7d43] text-white">
                    <x-icon name="whatsapp" class="size-8" />
                </span>
                <h3 class="mt-5 font-display text-2xl font-semibold">{{ __('app.cart.sent_title') }}</h3>

                {{-- The step people miss: WhatsApp only pre-fills the message --}}
                <ol class="mx-auto mt-5 max-w-sm space-y-2.5 text-start">
                    <li class="flex items-center gap-3 rounded-2xl bg-olive-soft/70 p-3 text-sm">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-olive text-paper"><x-icon name="check" class="size-4" /></span>
                        {{ __('app.cart.sent_step_ready') }}
                    </li>
                    <li class="flex items-center gap-3 rounded-2xl bg-saffron/20 p-3 text-sm font-medium text-clay-deep ring-1 ring-saffron/40">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-[#0f7d43] text-white">
                            <svg viewBox="0 0 24 24" class="flip-rtl size-4" fill="currentColor" aria-hidden="true"><path d="M3.4 20.4 21 12 3.4 3.6l-.01 6.53L15 12 3.39 13.87z"/></svg>
                        </span>
                        {{ __('app.cart.sent_step_send') }}
                    </li>
                </ol>

                <p class="mt-5 text-sm text-ink-mute">
                    {{ __('app.cart.reference') }}
                    <span class="ms-1 rounded-full bg-sand px-3 py-1 font-semibold tracking-wider text-ink" dir="ltr" x-text="'#' + sent.reference"></span>
                </p>
                <div class="mt-7 flex flex-col gap-2">
                    <button type="button" @click="openWhatsApp()" class="btn bg-[#0f7d43] text-white hover:bg-[#0c6a39]">
                        <x-icon name="whatsapp" class="size-5" /> {{ __('app.cart.open_again') }}
                    </button>
                    <button type="button" @click="close()" class="btn btn-ghost">{{ __('app.cart.new_order') }}</button>
                </div>
            </div>
        </template>

        {{-- Cart ------------------------------------------------------- --}}
        <template x-if="!sent">
            <div class="flex flex-1 flex-col">
                <div class="px-6">
                    <p x-show="notice" x-text="notice" role="alert" class="mb-3 rounded-2xl bg-saffron/20 px-4 py-3 text-sm text-clay-deep"></p>

                    <div x-show="!cartLines.length" class="py-12 text-center">
                        <p class="font-display text-xl font-semibold">{{ __('app.cart.empty') }}</p>
                        <p class="mt-1 text-ink-soft">{{ __('app.cart.empty_text') }}</p>
                    </div>

                    <ul class="divide-y divide-line/70">
                        <template x-for="line in cartLines" :key="line.id">
                            <li class="flex items-center gap-3 py-3">
                                <span class="arch grid h-14 w-12 shrink-0 place-items-center overflow-hidden bg-sand">
                                    <template x-if="line.thumb"><img :src="line.thumb" alt="" class="size-full object-cover"></template>
                                    <template x-if="!line.thumb"><span class="font-display text-lg text-ink-mute" x-text="line.name.charAt(0)"></span></template>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium" x-text="line.name"></span>
                                    <bdi class="text-sm text-brand-ink tabular-nums" x-text="money(line.total)"></bdi>
                                    <bdi x-show="line.discount > 0" class="ms-1.5 text-xs text-ink-mute tabular-nums line-through" x-text="money(line.full)"></bdi>
                                </span>
                                <span class="flex items-center rounded-full bg-sand p-0.5 ring-1 ring-line" role="group" :aria-label="line.name">
                                    <button type="button" @click="$store.cart.set(line.id, line.qty - 1)"
                                            class="grid size-8 place-items-center rounded-full transition hover:bg-paper active:scale-90"
                                            aria-label="{{ __('app.cart.decrease') }}">−</button>
                                    <span class="w-6 text-center text-sm font-semibold tabular-nums" x-text="line.qty"></span>
                                    <button type="button" @click="$store.cart.set(line.id, line.qty + 1)"
                                            class="grid size-8 place-items-center rounded-full transition hover:bg-paper active:scale-90"
                                            aria-label="{{ __('app.cart.increase') }}">+</button>
                                </span>
                            </li>
                        </template>
                    </ul>

                    @include('menu.partials.suggestions', ['list' => 'cartSuggestions'])
                </div>

                {{-- Order details --}}
                <form x-show="cartLines.length" @submit.prevent="send()" class="mt-2 flex flex-1 flex-col" novalidate>
                    <div class="space-y-4 border-t border-line/70 px-6 pt-5">
                        @if (count($types) > 1)
                            <fieldset>
                                <legend class="mb-2 text-sm font-medium text-ink-soft">{{ __('app.cart.type') }}</legend>
                                <div class="grid gap-1 rounded-full bg-sand p-1 ring-1 ring-line" style="grid-template-columns: repeat({{ count($types) }}, minmax(0, 1fr))">
                                    @foreach ($types as $type)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="type" value="{{ $type }}" x-model="$store.cart.details.type" @change="$store.cart.save()" class="peer sr-only">
                                            <span class="block rounded-full py-2 text-center text-sm font-medium text-ink-soft transition peer-checked:bg-ink peer-checked:text-paper peer-focus-visible:ring-4 peer-focus-visible:ring-brand/30">
                                                {{ __('app.cart.types.'.$type) }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <p x-show="errors.type" x-text="errors.type" class="mt-1.5 text-sm text-danger"></p>
                            </fieldset>
                        @endif

                        @if (in_array('dine_in', $types))
                            <label x-show="$store.cart.details.type === 'dine_in'" class="block space-y-1.5">
                                <span class="text-sm font-medium text-ink-soft">{{ __('app.cart.table') }} <span class="font-normal text-ink-mute">· {{ __('app.cart.optional') }}</span></span>
                                <input type="text" inputmode="numeric" maxlength="20" x-model="$store.cart.details.table" @input.debounce.400ms="$store.cart.save()" class="{{ $inputClass }}">
                                <p x-show="errors.table" x-text="errors.table" class="text-sm text-danger"></p>
                            </label>
                        @endif

                        <label x-show="$store.cart.details.type !== 'dine_in'" class="block space-y-1.5">
                            <span class="text-sm font-medium text-ink-soft">{{ __('app.cart.name') }}</span>
                            <input type="text" autocomplete="name" maxlength="60" x-model="$store.cart.details.name" @input.debounce.400ms="$store.cart.save()"
                                   :aria-invalid="!!errors.name" class="{{ $inputClass }}">
                            <p x-show="errors.name" x-text="errors.name" class="text-sm text-danger"></p>
                        </label>

                        @if (in_array('delivery', $types))
                            <label x-show="$store.cart.details.type === 'delivery'" class="block space-y-1.5">
                                <span class="text-sm font-medium text-ink-soft">{{ __('app.cart.address') }}</span>
                                <textarea rows="2" autocomplete="street-address" maxlength="300" x-model="$store.cart.details.address" @input.debounce.400ms="$store.cart.save()"
                                          placeholder="{{ __('app.cart.address_placeholder') }}" :aria-invalid="!!errors.address" class="{{ $inputClass }} resize-none"></textarea>
                                <p x-show="errors.address" x-text="errors.address" class="text-sm text-danger"></p>
                            </label>
                        @endif

                        <label class="block space-y-1.5">
                            <span class="text-sm font-medium text-ink-soft">{{ __('app.cart.note') }} <span class="font-normal text-ink-mute">· {{ __('app.cart.optional') }}</span></span>
                            <textarea rows="2" maxlength="300" x-model="$store.cart.details.note" placeholder="{{ __('app.cart.note_placeholder') }}" class="{{ $inputClass }} resize-none"></textarea>
                            <p x-show="errors.note" x-text="errors.note" class="text-sm text-danger"></p>
                        </label>
                    </div>

                    {{-- Total + send --}}
                    <div class="sticky bottom-0 mt-6 border-t border-line/70 bg-paper/95 px-6 pt-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] backdrop-blur">
                        <p x-show="savings > 0" class="mb-1.5 flex justify-between text-sm font-medium text-olive">
                            <span>{{ __('suggestions.menu.saved') }}</span><bdi class="tabular-nums" x-text="'−' + money(savings)"></bdi>
                        </p>
                        <div x-show="deliveryFee > 0" class="mb-1.5 space-y-1 text-sm text-ink-soft">
                            <p class="flex justify-between"><span>{{ __('shopinfo.cart.subtotal') }}</span><bdi class="tabular-nums" x-text="money(cartTotal)"></bdi></p>
                            <p class="flex justify-between"><span>{{ __('shopinfo.cart.delivery_fee') }}</span><bdi class="tabular-nums" x-text="money(deliveryFee)"></bdi></p>
                        </div>
                        <div class="mb-3 flex items-baseline justify-between">
                            <span class="text-ink-soft">{{ __('app.cart.total') }}</span>
                            <bdi class="text-2xl font-semibold tabular-nums" x-text="money(grandTotal)"></bdi>
                        </div>
                        <p x-show="belowMinimum" x-text="@js(__('shopinfo.cart.min_order', ['amount' => $shop->min_order !== null ? $shop->formatPrice($shop->min_order) : '']))" role="alert"
                           class="mb-3 rounded-2xl bg-saffron/20 px-4 py-2.5 text-center text-sm text-clay-deep"></p>
                        <button type="submit" :disabled="sending || belowMinimum" class="btn w-full bg-[#0f7d43] text-white shadow-[0_12px_28px_-14px_#0f7d43] hover:bg-[#0c6a39]">
                            <x-icon name="whatsapp" class="size-5" />
                            <span x-text="sending ? @js(__('app.cart.sending')) : @js(__('app.cart.send'))"></span>
                        </button>
                        <p class="mt-2.5 text-center text-sm text-ink-soft">{{ __('app.cart.send_hint') }}</p>
                        <p class="mt-1 text-center text-xs text-ink-mute">{{ __('app.cart.privacy') }}</p>
                    </div>
                </form>
            </div>
        </template>
    </section>
</div>
