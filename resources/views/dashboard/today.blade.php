@php
    $hoursClosed = $shop->canReceiveOrders() && $shop->orderingClosedReason() === 'hours';
    $symbol = \App\Support\Money::symbol($shop->currency);
    $input = 'field py-3';
@endphp
<x-layouts.dashboard :title="__('quick.today.title')">
    <x-page-header :title="__('quick.today.title')" :subtitle="__('quick.today.subtitle')" />

    <div x-data="quickEdit({
            items: @js($items),
            locales: @js($locales),
            urls: { product: @js(route('products.quick', ['product' => '__id__'])) },
            messages: { failed: @js(__('quick.failed')), saleEnded: @js(__('quick.sale.ended')) },
         })" @keydown.escape.window="editingId && close()" class="space-y-6">

        {{-- Taking orders ------------------------------------------------- --}}
        <section class="reveal card p-5 sm:p-6" style="--i: 1"
                 x-data="instantSwitch({ url: @js(route('ordering.pause')), field: 'orders_paused', on: @js(! $shop->orders_paused), invert: true, failedMessage: @js(__('quick.failed')) })">
            <div class="flex items-center gap-4">
                <div class="min-w-0 flex-1">
                    <h2 class="font-display text-xl font-semibold">{{ __('quick.pause.title') }}</h2>
                    <p class="mt-0.5 text-sm" :class="on ? 'text-olive' : 'text-clay'"
                       x-text="! on ? @js(__('quick.closed.paused')) : (@js($hoursClosed) ? @js(__('quick.closed.hours')) : @js(__('quick.pause.open')))"></p>
                </div>
                <button type="button" role="switch" :aria-checked="on.toString()" :disabled="busy" @click="toggle()"
                        aria-label="{{ __('quick.pause.title') }}"
                        class="relative h-9 w-16 shrink-0 rounded-full transition-colors duration-300 disabled:opacity-60"
                        :class="on ? 'bg-olive' : 'bg-sand-deep'">
                    <span class="absolute top-1 start-1 size-7 rounded-full bg-white shadow transition-transform duration-300 ease-(--ease-spring)"
                          :class="on && 'translate-x-7 rtl:-translate-x-7'"></span>
                </button>
            </div>
            <p class="mt-3 text-xs text-ink-mute">{{ __('quick.pause.hint') }}</p>
            <a href="{{ route('ordering.edit') }}" class="mt-3 inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-clay underline-offset-4 hover:underline">
                <x-icon name="clock" class="size-4" /> {{ __('quick.hours.link') }}
                <span class="font-normal text-ink-mute">· {{ $shop->order_hours ? __('quick.hours.on') : __('quick.hours.off') }}</span>
            </a>
        </section>

        {{-- Products ------------------------------------------------------- --}}
        <section class="reveal space-y-4" style="--i: 2">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-display text-xl font-semibold">{{ __('quick.products.title') }}</h2>
                <a href="{{ route('products.create') }}" class="inline-flex min-h-11 items-center gap-1 text-sm font-medium text-clay hover:underline">
                    <x-icon name="plus" class="size-4" /> {{ __('app.products.new') }}
                </a>
            </div>

            @if (count($items) === 0)
                <x-empty-state :title="__('app.products.empty_title')" :text="__('app.products.empty_text')" icon="cup" />
            @else
                <label class="relative block">
                    <span class="sr-only">{{ __('app.products.search') }}</span>
                    <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-ink-mute" />
                    <input type="search" x-model="q" placeholder="{{ __('app.products.search') }}" class="field ps-12" autocomplete="off">
                </label>

                @foreach ($categories as $category)
                    @continue($category->products->isEmpty())
                    <div>
                        <h3 class="mb-2 text-sm font-medium text-ink-soft">{{ $category->name }}</h3>
                        <ul class="card divide-y divide-line/70 overflow-hidden">
                            @foreach ($category->products as $product)
                                <li x-show="match({{ $product->id }})" class="flex items-center gap-3 px-3 py-3 sm:px-4"
                                    x-data="availabilityToggle({ url: @js(route('products.availability', $product)), on: @js($product->is_available), failedMessage: @js(__('app.products.toggle_failed')) })">
                                    <button type="button" @click="open({{ $product->id }})" class="flex min-w-0 flex-1 items-center gap-3 text-start"
                                            aria-label="{{ __('quick.edit.open', ['name' => $product->name]) }}">
                                        <span class="arch relative grid h-14 w-12 shrink-0 place-items-center overflow-hidden bg-sand ring-1 ring-line/80" :class="!on && 'grayscale opacity-50'">
                                            <img x-show="items[{{ $product->id }}].thumb" :src="items[{{ $product->id }}].thumb" alt="" loading="lazy" class="absolute inset-0 size-full object-cover">
                                            <span x-show="!items[{{ $product->id }}].thumb" class="font-display text-xl text-ink-mute" x-text="items[{{ $product->id }}].name.charAt(0)"></span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate font-medium" :class="!on && 'text-ink-mute'" x-text="items[{{ $product->id }}].name" dir="auto"></span>
                                            <span class="mt-0.5 flex flex-wrap items-baseline gap-x-2 text-sm">
                                                <bdi class="font-semibold text-clay" x-text="items[{{ $product->id }}].price_label"></bdi>
                                                <bdi x-show="items[{{ $product->id }}].on_sale" class="text-xs text-ink-mute line-through" x-text="items[{{ $product->id }}].compare_label"></bdi>
                                                <span class="inline-flex items-center gap-1 text-xs text-ink-mute"><x-icon name="pencil" class="size-3.5" /> {{ __('app.actions.edit') }}</span>
                                            </span>
                                        </span>
                                    </button>

                                    <button type="button" role="switch" :aria-checked="on.toString()" @click="toggle()"
                                            class="group flex min-h-11 shrink-0 flex-col items-center justify-center gap-1 text-[11px] font-medium"
                                            aria-label="{{ __('app.products.available') }}: {{ $product->name }}">
                                        <span class="relative h-7 w-12 rounded-full transition-colors duration-300" :class="on ? 'bg-olive' : 'bg-sand-deep'">
                                            <span class="absolute top-1 start-1 size-5 rounded-full bg-white shadow transition-transform duration-300 ease-(--ease-spring)"
                                                  :class="on && 'translate-x-5 rtl:-translate-x-5'"></span>
                                        </span>
                                        <span :class="on ? 'text-olive' : 'text-ink-mute'" x-text="on ? @js(__('app.products.available')) : @js(__('app.products.sold_out'))"></span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif
        </section>

        {{-- Promotions ----------------------------------------------------- --}}
        <section class="reveal space-y-3" style="--i: 3">
            <h2 class="font-display text-xl font-semibold">{{ __('quick.promos.title') }}</h2>

            @php($saleIds = collect($items)->keys()->all())
            <ul class="card divide-y divide-line/70 overflow-hidden" x-show="Object.values(items).some((item) => item.on_sale)">
                @foreach ($saleIds as $id)
                    <li x-show="items[{{ $id }}].on_sale" class="flex items-center gap-3 px-4 py-3">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium" x-text="items[{{ $id }}].name" dir="auto"></span>
                            <span class="text-sm text-ink-soft">{{ __('quick.sale.label') }}:
                                <bdi class="font-semibold text-clay" x-text="items[{{ $id }}].price_label"></bdi>
                                <bdi class="text-ink-mute line-through" x-text="items[{{ $id }}].compare_label"></bdi>
                            </span>
                        </span>
                        <button type="button" @click="endSale({{ $id }})" class="btn btn-ghost min-h-11 shrink-0 px-4 text-sm">{{ __('quick.sale.end') }}</button>
                    </li>
                @endforeach
            </ul>

            @if ($offers->isNotEmpty())
                <ul class="card divide-y divide-line/70 overflow-hidden">
                    @foreach ($offers as $offer)
                        <li class="flex items-center gap-3 px-4 py-3"
                            x-data="instantSwitch({ url: @js(route('offers.active', $offer)), field: 'is_active', on: @js($offer->is_active), failedMessage: @js(__('quick.failed')) })">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium" dir="auto">{{ $offer->product?->name }} <span class="text-ink-mute">+</span> {{ $offer->suggestedProduct?->name }}</span>
                                <span class="text-sm text-ink-soft">
                                    @if ($offer->discount_type === 'percent')
                                        {{ __('suggestions.menu.percent_off', ['value' => rtrim(rtrim((string) $offer->discount_value, '0'), '.')]) }}
                                    @elseif ($offer->discount_type === 'fixed')
                                        {{ __('suggestions.menu.amount_off', ['amount' => $shop->formatPrice($offer->discount_value)]) }}
                                    @else
                                        {{ __('suggestions.discount.none') }}
                                    @endif
                                </span>
                            </span>
                            <button type="button" role="switch" :aria-checked="on.toString()" :disabled="busy" @click="toggle()"
                                    class="flex min-h-11 shrink-0 flex-col items-center justify-center gap-1 text-[11px] font-medium"
                                    aria-label="{{ __('suggestions.fields.active') }}">
                                <span class="relative h-7 w-12 rounded-full transition-colors duration-300" :class="on ? 'bg-olive' : 'bg-sand-deep'">
                                    <span class="absolute top-1 start-1 size-5 rounded-full bg-white shadow transition-transform duration-300 ease-(--ease-spring)"
                                          :class="on && 'translate-x-5 rtl:-translate-x-5'"></span>
                                </span>
                                <span :class="on ? 'text-olive' : 'text-ink-mute'" x-text="on ? @js(__('quick.promos.on')) : @js(__('quick.promos.off'))"></span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="text-sm text-ink-mute">{{ __('quick.promos.hint') }}</p>
        </section>

        {{-- Edit sheet ------------------------------------------------------ --}}
        <div x-show="editingId" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="quick-title">
            <div class="absolute inset-0 bg-ink/50" @click="close()"></div>

            <form @submit.prevent="save()" novalidate
                  x-transition:enter="transition duration-300 ease-(--ease-spring)" x-transition:enter-start="translate-y-8 opacity-0"
                  class="absolute inset-x-0 bottom-0 mx-auto flex max-h-[94dvh] max-w-xl flex-col overflow-y-auto overscroll-contain rounded-t-[2rem] bg-paper shadow-2xl sm:bottom-auto sm:top-1/2 sm:-translate-y-1/2 sm:rounded-[2rem]">
                <div class="sticky top-0 z-10 flex items-center justify-between gap-3 bg-paper/95 px-5 pt-5 pb-3 backdrop-blur">
                    <h2 id="quick-title" class="truncate font-display text-xl font-semibold" x-text="item?.name" dir="auto"></h2>
                    <button type="button" @click="close()" class="grid size-11 shrink-0 place-items-center rounded-full bg-sand transition active:scale-90" aria-label="{{ __('app.actions.cancel') }}">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>

                <div class="space-y-5 px-5 pb-5">
                    <p x-show="errors.length" x-cloak role="alert" class="rounded-2xl bg-danger/10 p-3 text-sm text-danger ring-1 ring-danger/20">
                        <template x-for="message in errors" :key="message"><span class="block" x-text="message"></span></template>
                    </p>

                    {{-- Photo --}}
                    <div class="flex items-center gap-4">
                        <span class="arch relative grid h-24 w-20 shrink-0 place-items-center overflow-hidden bg-sand ring-1 ring-line">
                            <img x-show="preview" :src="preview" alt="" class="absolute inset-0 size-full object-cover">
                            <x-icon name="image" class="size-6 text-ink-mute" x-show="!preview" />
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <label class="btn btn-ghost min-h-11 cursor-pointer px-4 text-sm">
                                <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pickFile($event)">
                                {{ __('app.image.change') }}
                            </label>
                            <button type="button" x-show="preview" @click="dropImage()" class="btn min-h-11 px-4 text-sm text-danger hover:bg-danger/10">{{ __('app.image.remove') }}</button>
                        </div>
                    </div>

                    {{-- Names and descriptions, one language at a time --}}
                    @foreach ([$shop->default_locale] as $locale)
                        <div class="space-y-4">
                            <label class="block space-y-1.5">
                                <span class="text-sm font-medium text-ink-soft">{{ __('app.fields.name') }}
                                    @if ($locale === $shop->default_locale)<span class="text-[11px] font-normal text-clay">{{ __('app.forms.required') }}</span>@endif
                                </span>
                                <input x-model="form.names.{{ $locale }}" maxlength="80" lang="{{ $locale }}" dir="{{ \App\Support\Locales::dir($locale) }}" class="{{ $input }}">
                            </label>
                            <label class="block space-y-1.5">
                                <span class="text-sm font-medium text-ink-soft">{{ __('app.fields.description') }}</span>
                                <textarea x-model="form.descriptions.{{ $locale }}" rows="3" maxlength="400" lang="{{ $locale }}" dir="{{ \App\Support\Locales::dir($locale) }}" class="{{ $input }} resize-y"></textarea>
                            </label>
                        </div>
                    @endforeach
                    @if (count($locales) > 1)
                        <p class="text-xs text-ink-mute">{{ __('app.forms.auto_translate_note') }}</p>
                    @endif

                    {{-- Prices --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach (['price' => ['price', __('app.fields.price')], 'compare' => ['compare', __('quick.edit.compare')]] as [$model, $label])
                            <label class="block space-y-1.5">
                                <span class="text-sm font-medium text-ink-soft">{{ $label }}</span>
                                <div dir="ltr" class="relative">
                                    <input x-model="form.{{ $model }}" inputmode="decimal" autocomplete="off" class="{{ $input }} pe-14 font-medium tabular-nums rtl:text-right">
                                    <span class="pointer-events-none absolute inset-y-0 end-4 grid place-items-center text-ink-mute">{{ $symbol }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-ink-mute">{{ __('quick.edit.compare_hint') }}</p>
                </div>

                <div class="sticky bottom-0 flex items-center justify-between gap-2 border-t border-line/70 bg-paper/95 px-5 py-4 backdrop-blur">
                    <a :href="@js(route('products.edit', ['product' => '__id__'])).replace('__id__', editingId)" class="text-sm text-ink-soft underline-offset-4 hover:underline">{{ __('quick.edit.more') }}</a>
                    <div class="flex gap-2">
                        <button type="button" @click="close()" class="btn btn-ghost min-h-11">{{ __('app.actions.cancel') }}</button>
                        <button type="submit" class="btn btn-primary min-h-11 min-w-28" :disabled="busy">
                            <span x-show="!busy">{{ __('app.actions.save') }}</span>
                            <span x-show="busy" x-cloak>{{ __('app.actions.saving') }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.dashboard>
