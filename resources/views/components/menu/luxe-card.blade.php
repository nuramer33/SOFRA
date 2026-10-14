{{-- One product in the Luxe gallery: a tall arched photo, then name and price.
     The whole tile opens the product sheet; the round button adds one to the order. --}}
@props(['product', 'shop'])
@php($orderable = $shop->acceptsOrders() && $product->is_available)
<article data-card data-search="{{ mb_strtolower(implode(' ', [...array_values($product->getTranslations('name')), ...array_values($product->getTranslations('description'))])) }}" data-reveal
         class="group relative min-w-0 [&:nth-child(even)]:mt-10">
    <button type="button" class="absolute inset-0 z-10" @click="open({{ $product->id }}, $el)">
        <span class="sr-only">{{ $product->name }} — {{ __('app.menu.details') }}</span>
    </button>

    <div data-photo class="lx-niche relative aspect-[3/4] overflow-hidden bg-sand ring-1 ring-line/70 transition-transform duration-300 group-active:scale-[0.98]">
        @if ($product->image_path)
            <img src="{{ $product->imageUrl('sm') }}" alt="" loading="lazy" decoding="async"
                 @class(['img-fade size-full object-cover', 'grayscale opacity-60' => ! $product->is_available])>
        @else
            <span class="grid size-full place-items-center font-display text-6xl font-light text-brand-ink/50">{{ mb_substr($product->name, 0, 1) }}</span>
        @endif

        @unless ($product->is_available)
            <span class="absolute inset-x-0 bottom-0 bg-paper/80 py-2 text-center text-xs text-ink-soft backdrop-blur">{{ __('app.menu.sold_out') }}</span>
        @endunless

        @if ($orderable)
            <button type="button" @click.stop="quickAdd({{ $product->id }}, $el)"
                    class="absolute end-2.5 bottom-2.5 z-20 grid size-10 place-items-center rounded-full border border-brand/70 bg-paper/55 text-brand-ink backdrop-blur transition active:scale-90"
                    :class="$store.cart.qty({{ $product->id }}) && '!bg-brand !text-brand-fg'"
                    :aria-label="@js(__('app.cart.add').' — '.$product->name)">
                <span x-show="!$store.cart.qty({{ $product->id }})">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                </span>
                <span x-show="$store.cart.qty({{ $product->id }})" x-cloak x-text="$store.cart.qty({{ $product->id }})" class="text-sm font-medium tabular-nums"></span>
            </button>
        @endif
    </div>

    <h3 @class(['mt-4 font-display text-lg leading-snug font-normal text-balance', 'text-ink-soft' => ! $product->is_available])>{{ $product->name }}</h3>
    @if ($product->description)
        <p class="mt-1 line-clamp-2 text-[13px] leading-relaxed text-ink-mute">{{ $product->description }}</p>
    @endif
    <p class="lx-caption mt-2 flex flex-wrap items-baseline gap-x-2 text-sm">
        <bdi class="font-medium tabular-nums text-brand-ink">{{ $shop->formatPrice($product->price) }}</bdi>
        @if ($product->isOnSale())
            <bdi class="text-xs tabular-nums text-ink-mute line-through">{{ $shop->formatPrice($product->compare_at_price) }}</bdi>
        @endif
    </p>
</article>
