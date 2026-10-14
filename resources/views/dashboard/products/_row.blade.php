{{-- One product in the list. Expects $product, $category, $shop, $searchText, $loop. --}}
<li id="product-{{ $product->id }}"
    x-show="matches(@js($searchText($product)), {{ $category->id }})"
    x-data="availabilityToggle({ url: @js(route('products.availability', $product)), on: @js($product->is_available), failedMessage: @js(__('app.products.toggle_failed')) })"
    class="flex items-center gap-3 px-3 py-3 sm:gap-4 sm:px-4">

    <a href="{{ route('products.edit', $product) }}" class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
        <x-thumb :src="$product->imageUrl('sm')" :label="$product->name"
                 class="transition duration-300" x-bind:class="!on && 'grayscale opacity-50'" />
        <span class="min-w-0">
            <span class="flex items-center gap-1.5">
                <span class="truncate font-medium" :class="!on && 'text-ink-mute'">{{ $product->name }}</span>
                @if ($product->is_featured)
                    <x-icon name="star" class="size-4 shrink-0 fill-saffron text-saffron" title="{{ __('app.products.featured') }}" />
                @endif
            </span>
            <span class="mt-0.5 flex flex-wrap items-baseline gap-x-2 text-sm">
                <bdi class="font-semibold text-clay">{{ $shop->formatPrice($product->price) }}</bdi>
                @if ($product->isOnSale())
                    <bdi class="text-xs text-ink-mute line-through">{{ $shop->formatPrice($product->compare_at_price) }}</bdi>
                    <span class="rounded-full bg-saffron/20 px-1.5 text-[11px] font-medium text-clay-deep">{{ __('app.products.on_sale') }}</span>
                @endif
                <span class="hidden sm:inline"><x-translation-dots :model="$product" field="name" /></span>
            </span>
        </span>
    </a>

    {{-- Available / sold out: saves instantly --}}
    <button type="button" role="switch" :aria-checked="on.toString()" @click="toggle()"
            class="group flex shrink-0 flex-col items-center gap-1 text-[11px] font-medium"
            aria-label="{{ __('app.products.available') }}: {{ $product->name }}">
        <span class="relative h-7 w-12 rounded-full transition-colors duration-300" :class="on ? 'bg-olive' : 'bg-sand-deep'">
            <span class="absolute top-1 start-1 size-5 rounded-full bg-white shadow transition-transform duration-300 ease-(--ease-spring)"
                  :class="on && 'translate-x-5 rtl:-translate-x-5'"></span>
        </span>
        <span :class="on ? 'text-olive' : 'text-ink-mute'" x-text="on ? @js(__('app.products.available')) : @js(__('app.products.sold_out'))">
            {{ $product->is_available ? __('app.products.available') : __('app.products.sold_out') }}
        </span>
    </button>

    {{-- ⋮ menu --}}
    <div class="relative shrink-0" x-data="{ open: false }" @keydown.escape="open = false" @click.outside="open = false">
        <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                class="grid size-9 place-items-center rounded-full text-ink-soft transition hover:bg-sand hover:text-ink"
                aria-label="{{ __('app.actions.more') }}">
            <svg viewBox="0 0 24 24" class="size-5" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg>
        </button>
        <div x-show="open" x-cloak x-transition.origin.top
             class="absolute end-0 top-full z-20 mt-1 w-48 overflow-hidden rounded-2xl bg-paper py-1.5 text-sm shadow-xl ring-1 ring-line">
            <a href="{{ route('products.edit', $product) }}" class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-sand">
                <x-icon name="pencil" class="size-4 text-ink-soft" /> {{ __('app.actions.edit') }}
            </a>
            <a href="{{ route('products.suggestions.edit', $product) }}" class="flex items-center gap-2.5 px-4 py-2.5 hover:bg-sand">
                <x-icon name="plus" class="size-4 text-ink-soft" /> {{ __('suggestions.menu_link') }}
            </a>
            @foreach (['up' => ! $loop->first, 'down' => ! $loop->last] as $direction => $enabled)
                @if ($enabled)
                    <form method="POST" action="{{ route('products.position', $product) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="direction" value="{{ $direction }}">
                        <button class="flex w-full items-center gap-2.5 px-4 py-2.5 hover:bg-sand">
                            <x-icon name="chevron-{{ $direction }}" class="size-4 text-ink-soft" /> {{ __('app.actions.move_'.$direction) }}
                        </button>
                    </form>
                @endif
            @endforeach
            <button type="button" class="flex w-full items-center gap-2.5 border-t border-line px-4 py-2.5 text-danger hover:bg-danger/5"
                    @click="open = false; $dispatch('delete-product-{{ $product->id }}')">
                <x-icon name="trash" class="size-4" /> {{ __('app.actions.delete') }}
            </button>
        </div>
    </div>

    <x-delete-dialog :action="route('products.destroy', $product)" event="delete-product-{{ $product->id }}"
                     :title="__('app.products.delete_title', ['name' => $product->name])"
                     :text="__('app.products.delete_text')" />
</li>
