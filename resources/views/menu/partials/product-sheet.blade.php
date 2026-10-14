{{-- Product details sheet + the JSON the menu script reads. Shared by every menu template; colors come from the theme variables. --}}
        {{-- Product sheet ------------------------------------------------- --}}
        <script type="application/json" id="menu-data">@json($sheetData)</script>
        <script type="application/json" id="menu-config">@json($menuConfig)</script>

        {{-- Not x-show: Alpine delays x-show with a setTimeout, which the
             View Transition would miss. A class binding applies instantly. --}}
        <div class="fixed inset-0 z-50 hidden" :class="{ 'hidden': !product, 'is-closing': closing }"
             @keydown.escape.window="product && close()">
            <div class="sheet-backdrop absolute inset-0 bg-ink/50" @click="close()"></div>

            <section x-ref="panel" role="dialog" aria-modal="true" :aria-label="product?.name"
                     class="sheet-panel absolute inset-x-0 bottom-0 mx-auto max-h-[92dvh] max-w-2xl overflow-y-auto overscroll-contain rounded-t-[2rem] bg-paper shadow-2xl"
                     :class="dragY ? '' : 'transition-transform duration-300 ease-(--ease-soft)'"
                     :style="dragY && sheet === 'product' ? `transform: translateY(${dragY}px)` : ''">
                <div class="sticky top-0 z-10 flex cursor-grab touch-none justify-center pt-3 pb-2" @pointerdown="dragStart($event)">
                    <span class="h-1.5 w-12 rounded-full bg-ink/20"></span>
                </div>

                <button type="button" x-ref="close" @click="close()"
                        class="absolute top-5 end-5 z-20 grid size-10 place-items-center rounded-full bg-paper/90 text-ink shadow-md ring-1 ring-line backdrop-blur transition active:scale-90"
                        aria-label="{{ __('app.menu.close') }}">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>

                <template x-if="product">
                    <div class="px-4 pb-[max(2rem,env(safe-area-inset-bottom))]">
                        <template x-if="product.image">
                            <div x-ref="hero" class="aspect-[4/3] overflow-hidden rounded-[1.5rem] bg-sand">
                                <img :src="product.image" alt="" class="size-full object-cover" :class="!product.available && 'grayscale'">
                            </div>
                        </template>

                        <div class="px-2 pt-6" :class="!product.image && 'pt-12'">
                            <h2 class="font-display text-3xl leading-tight font-semibold text-balance" x-text="product.name"></h2>
                            <p x-show="product.description" class="mt-3 leading-relaxed text-ink-soft" x-text="product.description"></p>

                            <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-line pt-5">
                                <bdi class="text-2xl font-semibold text-brand-ink" x-text="product.price"></bdi>
                                <bdi x-show="product.compare" class="text-ink-mute line-through" x-text="product.compare"></bdi>
                                <span x-show="!product.available" class="rounded-full bg-sand px-3 py-1 text-sm font-medium text-ink-soft">{{ __('app.menu.sold_out') }}</span>
                            </div>
                        </div>

                        @if ($shop->acceptsOrders())
                            @include('menu.partials.suggestions', ['list' => 'sheetSuggestions'])

                            {{-- Quantity + add --}}
                            <div x-show="product.available" class="sticky bottom-0 -mx-4 mt-6 border-t border-line/70 bg-paper/95 px-4 pt-4 backdrop-blur">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center rounded-full bg-sand p-1 ring-1 ring-line" role="group" aria-label="{{ __('app.cart.quantity') }}">
                                        <button type="button" @click="qty = Math.max(1, qty - 1)" :disabled="qty <= 1"
                                                class="grid size-10 place-items-center rounded-full text-xl transition hover:bg-paper active:scale-90 disabled:opacity-30"
                                                aria-label="{{ __('app.cart.decrease') }}">−</button>
                                        <span class="w-8 text-center text-lg font-semibold tabular-nums" x-text="qty" aria-live="polite"></span>
                                        <button type="button" @click="qty = Math.min(99, qty + 1)"
                                                class="grid size-10 place-items-center rounded-full text-xl transition hover:bg-paper active:scale-90"
                                                aria-label="{{ __('app.cart.increase') }}">+</button>
                                    </div>
                                    <button type="button" @click="addFromSheet()"
                                            class="btn flex-1 justify-between bg-brand px-5 text-brand-fg shadow-[0_10px_24px_-12px_var(--brand)] hover:brightness-110">
                                        <span x-text="$store.cart.qty(productId) ? @js(__('app.cart.update')) : @js(__('app.cart.add_to_cart'))"></span>
                                        <bdi class="tabular-nums" x-text="money(product.amount * qty)"></bdi>
                                    </button>
                                </div>
                                <button type="button" x-show="$store.cart.qty(productId)" @click="removeFromSheet()"
                                        class="mx-auto mt-2 block text-sm text-ink-mute underline-offset-4 hover:text-danger hover:underline">
                                    {{ __('app.cart.remove_from_cart') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </template>
            </section>
        </div>
