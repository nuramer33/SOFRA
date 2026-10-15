@php
    $shop = app(\App\Tenancy\CurrentShop::class)->get();
    $productCount = $categories->sum(fn ($category) => $category->products->count());

    // Every language's name is searchable, whatever the interface language.
    $searchText = fn ($product) => implode(' ', $product->getTranslations('name'));
    $entries = $categories->flatMap(fn ($category) => $category->products->map(
        fn ($product) => [$searchText($product), $category->id]
    ))->values();
@endphp
<x-layouts.dashboard :title="__('app.products.title')">
    <x-page-header :title="__('app.products.title')" :subtitle="__('app.products.subtitle')">
        @if ($productCount > 0)
            <x-slot:actions>
                <a href="{{ route('today') }}" class="btn btn-ghost">{{ __('quick.today.title') }}</a>
                <a href="{{ route('products.create') }}" class="btn btn-primary">
                    <x-icon name="plus" class="size-4" /> {{ __('app.products.new') }}
                </a>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if ($categories->isEmpty())
        <x-empty-state :title="__('app.products.empty_title')" :text="__('app.products.need_category')" icon="grid">
            <x-slot:action>
                <a href="{{ route('categories.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> {{ __('app.categories.new') }}</a>
            </x-slot:action>
        </x-empty-state>
    @elseif ($productCount === 0)
        <x-empty-state :title="__('app.products.empty_title')" :text="__('app.products.empty_text')" icon="cup">
            <x-slot:action>
                <a href="{{ route('products.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> {{ __('app.products.new') }}</a>
            </x-slot:action>
        </x-empty-state>
    @else
        <div x-data="productFilter()">
            {{-- Search + category chips --}}
            <div class="reveal sticky top-[4.3rem] z-10 -mx-4 mb-6 space-y-3 bg-paper/90 px-4 py-3 backdrop-blur-md sm:mx-0 sm:rounded-3xl sm:px-0 lg:top-4" style="--i: 1">
                <label class="relative block">
                    <span class="sr-only">{{ __('app.products.search') }}</span>
                    <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-ink-mute" />
                    <input type="search" x-model="query" placeholder="{{ __('app.products.search') }}" class="field ps-12" autocomplete="off">
                </label>
                <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] sm:mx-0 sm:px-0">
                    <button type="button" @click="category = 'all'"
                            :class="category === 'all' ? 'bg-ink text-paper' : 'bg-white/70 text-ink-soft ring-1 ring-line hover:text-ink'"
                            class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors">{{ __('app.products.all') }}</button>
                    @foreach ($categories as $category)
                        <button type="button" @click="category = {{ $category->id }}"
                                :class="category === {{ $category->id }} ? 'bg-ink text-paper' : 'bg-white/70 text-ink-soft ring-1 ring-line hover:text-ink'"
                                class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors">
                            {{ $category->name }} <span class="opacity-60">{{ $category->products->count() }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <p x-show="noResults(@js($entries))" x-cloak class="card px-6 py-10 text-center text-ink-soft">{{ __('app.products.no_results') }}</p>

            <div class="space-y-8">
                @foreach ($categories as $category)
                    <section x-show="sectionVisible(@js($category->products->map($searchText)->values()), {{ $category->id }})"
                             class="reveal" style="--i: {{ min($loop->index + 2, 8) }}">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 class="flex items-center gap-2 font-display text-xl font-semibold">
                                {{ $category->name }}
                                @unless ($category->is_active)
                                    <span class="rounded-full bg-sand px-2 py-0.5 font-sans text-[11px] font-normal text-ink-soft">{{ __('app.categories.hidden') }}</span>
                                @endunless
                            </h2>
                            <a href="{{ route('products.create', ['category' => $category->id]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-clay hover:underline underline-offset-4">
                                <x-icon name="plus" class="size-4" /> {{ __('app.products.add_here') }}
                            </a>
                        </div>

                        @if ($category->products->isEmpty())
                            <p class="rounded-3xl border border-dashed border-line px-5 py-6 text-center text-sm text-ink-mute">{{ __('app.products.empty_category') }}</p>
                        @else
                            <ul class="card divide-y divide-line/70 overflow-visible" x-data="sortable({ url: @js(route('products.reorder', $category)), failedMessage: @js(__('app.actions.reorder_failed')) })"
                                @pointermove="move($event)" @pointerup="end()" @pointercancel="end()">
                                @foreach ($category->products as $product)
                                    @include('dashboard.products._row')
                                @endforeach
                            </ul>
                        @endif
                    </section>
                @endforeach
            </div>
        </div>
    @endif
</x-layouts.dashboard>
