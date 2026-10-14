{{-- One dish in a category list. The whole card opens the product sheet;
     the round button adds one straight to the order. --}}
@props(['product', 'shop'])
@php($orderable = $shop->acceptsOrders() && $product->is_available)
<article data-card data-search="{{ mb_strtolower(implode(' ', [...array_values($product->getTranslations('name')), ...array_values($product->getTranslations('description'))])) }}" data-reveal @class([
    'group relative flex items-stretch gap-4 rounded-[1.75rem] bg-white/65 p-3 ring-1 ring-line/70 transition-transform duration-200 active:scale-[0.985]',
    'opacity-75' => ! $product->is_available,
])>
    <button type="button" class="absolute inset-0 z-10 rounded-[1.75rem]" @click="open({{ $product->id }}, $el)">
        <span class="sr-only">{{ $product->name }} — {{ __('app.menu.details') }}</span>
    </button>

    <div @class(['flex min-w-0 flex-1 flex-col py-1.5 ps-1.5', 'pe-10' => $orderable && ! $product->image_path])>
        <h3 class="text-[1.05rem] leading-snug font-semibold text-balance">{{ $product->name }}</h3>
        @if ($product->description)
            <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-ink-soft">{{ $product->description }}</p>
        @endif

        <div class="mt-auto flex flex-wrap items-center gap-x-2 gap-y-1 pt-3">
            <bdi class="font-semibold text-brand-ink">{{ $shop->formatPrice($product->price) }}</bdi>
            @if ($product->isOnSale())
                <bdi class="text-xs text-ink-mute line-through">{{ $shop->formatPrice($product->compare_at_price) }}</bdi>
                <span class="rounded-full bg-saffron/25 px-2 py-0.5 text-[11px] font-medium text-clay-deep">{{ __('app.menu.on_sale') }}</span>
            @endif
            @unless ($product->is_available)
                <span class="rounded-full bg-sand px-2 py-0.5 text-[11px] font-medium text-ink-soft">{{ __('app.menu.sold_out') }}</span>
            @endunless
        </div>
    </div>

    @if ($product->image_path)
        <div data-photo class="arch relative h-[7.5rem] w-[6.25rem] shrink-0 overflow-hidden bg-sand">
            <img src="{{ $product->imageUrl('sm') }}" alt="" loading="lazy" decoding="async"
                 @class(['img-fade size-full object-cover transition-transform duration-700 ease-(--ease-soft) group-hover:scale-105', 'grayscale' => ! $product->is_available])>
        </div>
    @endif

    @if ($orderable)
        {{-- Quick add: shows "+" or the quantity already in the order --}}
        <button type="button" @click.stop="quickAdd({{ $product->id }}, $el)"
                class="absolute end-2 bottom-2 z-20 grid size-10 place-items-center rounded-full bg-brand font-semibold text-brand-fg shadow-[0_8px_18px_-8px_var(--brand)] ring-4 ring-paper transition active:scale-90"
                :aria-label="@js(__('app.cart.add').' — '.$product->name)">
            <span x-show="!$store.cart.qty({{ $product->id }})">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            </span>
            <span x-show="$store.cart.qty({{ $product->id }})" x-cloak x-text="$store.cart.qty({{ $product->id }})" class="text-sm tabular-nums"></span>
        </button>
    @endif
</article>
