{{--
    Menu template 2 — "Luxe"
    A boutique catalogue for perfume, accessories and gifts: a deep field in
    the shop's own color, one large name, and a two-column gallery of arched
    display niches. Same behavior as Classic (cart, offers, hours); only the
    look differs. Colors are the theme variables set in menu.css (.theme-luxe).
--}}
@php
    $locales = $shop->contentLocales();
    $description = $shop->tagline ?: __('app.tagline');
    $shareImage = app(\App\Support\ShareImage::class)->url($shop);
    $alternates = collect($locales)->mapWithKeys(fn ($locale) => [$locale => route('menu.show', [$shop, 'lang' => $locale])])->all();

    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $shop->name,
        'description' => $description,
        'url' => route('menu.show', $shop),
        'image' => $shareImage,
        'hasMenu' => route('menu.show', $shop),
        'telephone' => $shop->whatsapp_number ? '+'.$shop->whatsapp_number : null,
        'address' => $shop->address ? ['@type' => 'PostalAddress', 'streetAddress' => $shop->address] : null,
        'sameAs' => $shop->instagram ? ['https://instagram.com/'.$shop->instagram] : null,
    ]);
@endphp
<x-layouts.base :exact-title="$shop->name.($shop->tagline ? ' — '.$shop->tagline : '')"
                html-class="is-menu" :theme-color="$shop->brand_color" :scripts="['resources/js/menu.js']"
                body-class="theme-luxe"
                style="--lx: {{ $shop->brand_color }};">
    <x-slot:head>
        <x-seo :title="$shop->name" :description="$description" :url="route('menu.show', [$shop, 'lang' => app()->getLocale()])"
               :image="$shareImage" :alternates="$alternates" :json-ld="$jsonLd" />
        <script>
            (function () {
                var d = document.documentElement, key = 'sofra-intro-{{ $shop->id }}';
                d.classList.add('js');
                try {
                    if (!matchMedia('(prefers-reduced-motion: reduce)').matches && !sessionStorage.getItem(key)) {
                        d.classList.add('intro');
                        sessionStorage.setItem(key, '1');
                    }
                } catch (e) {}
            })();
        </script>
    </x-slot:head>

    {{-- First-visit curtain: the name alone, on the shop's field color --}}
    <div class="splash" aria-hidden="true">
        <div class="flex flex-col items-center">
            <div class="splash-arch lx-niche grid h-44 w-32 place-items-center overflow-hidden ring-1 ring-ink/25">
                <div class="splash-logo grid size-full place-items-center"><x-menu.monogram :shop="$shop" size="text-5xl" cover /></div>
            </div>
            <div class="mt-7 overflow-hidden px-6 pb-1">
                <p class="splash-name text-center font-display text-3xl font-light">{{ $shop->name }}</p>
            </div>
        </div>
        <span class="absolute bottom-[max(2rem,env(safe-area-inset-bottom))] text-xs opacity-60">{{ __('app.menu.skip') }}</span>
    </div>

    <div x-data="menuPage">
        {{-- Hero ---------------------------------------------------------- --}}
        <header class="relative">
            <div class="mx-auto max-w-2xl px-6 pt-5 pb-14">
                @if (count($locales) > 1)
                    <nav class="hero-in relative z-20 flex justify-end" aria-label="{{ __('app.menu.language') }}">
                        <x-locale-switcher :locales="$locales"
                                           :urls="collect($locales)->mapWithKeys(fn ($locale) => [$locale => route('menu.show', [$shop, 'lang' => $locale])])->all()" />
                    </nav>
                @endif

                <div class="mt-8 flex flex-col items-center text-center">
                    <div class="hero-in lx-niche relative grid h-36 w-28 place-items-center overflow-hidden text-brand-ink ring-1 ring-brand/50" style="--i: 1">
                        <x-menu.monogram :shop="$shop" size="text-5xl" cover />
                    </div>

                    <h1 class="lx-name hero-in mt-9" style="--i: 2">{{ $shop->name }}</h1>

                    @if ($shop->tagline)
                        <p class="hero-in mt-4 max-w-sm text-ink-soft text-balance" style="--i: 3">{{ $shop->tagline }}</p>
                    @endif

                    @if ($shop->maps_url || $shop->instagram)
                        <div class="hero-in mt-7 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm" style="--i: 4">
                            @if ($shop->maps_url)
                                <a href="{{ $shop->maps_url }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-2 text-ink underline decoration-brand/50 underline-offset-[6px] transition hover:decoration-brand">
                                    <x-icon name="pin" class="size-4 text-brand-ink" /> {{ __('shopinfo.menu.directions') }}
                                </a>
                            @endif
                            @if ($shop->instagram)
                                <a href="https://instagram.com/{{ $shop->instagram }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-2 text-ink underline decoration-brand/50 underline-offset-[6px] transition hover:decoration-brand">
                                    <x-icon name="instagram" class="size-4 text-brand-ink" /> <span dir="ltr">{{ $shop->instagram }}</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            <div id="hero-end" aria-hidden="true"></div>
        </header>

        @include('menu.partials.status-notice')

        @if ($categories->isEmpty())
            <main class="mx-auto flex max-w-2xl flex-col items-center px-6 py-20 text-center">
                <div class="lx-niche h-28 w-20 ring-1 ring-brand/50" aria-hidden="true"></div>
                <h2 class="mt-6 font-display text-3xl font-light">{{ __('app.menu.empty_title') }}</h2>
                <p class="mt-2 text-ink-soft">{{ __('app.menu.empty_text') }}</p>
            </main>
        @else
            {{-- Sticky category bar: plain words, one hairline slides under the active one --}}
            <nav x-ref="bar" class="sticky top-0 z-30 border-y border-line bg-paper/90 backdrop-blur-md" aria-label="{{ __('app.menu.sections') }}">
                <div class="mx-auto max-w-2xl">
                    <div class="grid transition-[grid-template-rows] duration-500 ease-(--ease-soft)" :class="compact ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                        <div class="overflow-hidden">
                            <button type="button" @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                                    class="menu-bar-brand flex min-h-11 items-center px-6 pt-2" :class="compact ? 'opacity-100' : 'opacity-0 -translate-y-2'"
                                    :tabindex="compact ? 0 : -1">
                                <span class="truncate font-display text-lg font-light">{{ $shop->name }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto px-6 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <div x-ref="chips" class="relative flex w-max gap-7">
                            <span x-ref="indicator" class="chip-indicator absolute bottom-0 left-0 h-px bg-brand opacity-0" aria-hidden="true"></span>
                            @foreach ($categories as $category)
                                <button type="button" data-chip="{{ $category->id }}" @click="go('{{ $category->id }}')"
                                        class="relative z-10 min-h-12 text-sm whitespace-nowrap transition-colors duration-300"
                                        :class="active === '{{ $category->id }}' ? 'text-ink' : 'text-ink-mute hover:text-ink'"
                                        :aria-current="active === '{{ $category->id }}' ? 'true' : null">
                                    {{ $category->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </nav>

            <main>
                @if ($productCount >= 8)
                    <div class="mx-auto max-w-2xl px-6 pt-8" data-search-box>
                        <label class="relative block">
                            <span class="sr-only">{{ __('shopinfo.menu.search_placeholder') }}</span>
                            <x-icon name="search" class="pointer-events-none absolute start-0 top-1/2 size-5 -translate-y-1/2 text-ink-mute" />
                            <input type="search" x-model="query" @input.debounce.150ms="search()" autocomplete="off"
                                   placeholder="{{ __('shopinfo.menu.search_placeholder') }}"
                                   class="field !rounded-none !border-x-0 !border-t-0 !bg-transparent !px-0 ps-8">
                        </label>
                    </div>
                @endif

                {{-- Featured: a row of large niches ------------------------- --}}
                @if ($featured->isNotEmpty())
                    <section data-featured x-show="!query.trim()" class="mx-auto max-w-2xl pt-12" aria-labelledby="featured-title">
                        <div class="px-6" data-reveal>
                            <h2 id="featured-title" class="font-display text-3xl font-light">{{ __('app.menu.featured') }}</h2>
                            <p class="mt-1 text-sm text-ink-mute">{{ __('app.menu.featured_hint') }}</p>
                        </div>
                        <div class="mt-6 flex snap-x snap-mandatory scroll-px-6 gap-5 overflow-x-auto px-6 pb-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            @foreach ($featured as $product)
                                <article data-card data-reveal class="group relative w-[68vw] max-w-64 shrink-0 snap-start">
                                    <button type="button" class="absolute inset-0 z-10" @click="open({{ $product->id }}, $el)">
                                        <span class="sr-only">{{ $product->name }} — {{ __('app.menu.details') }}</span>
                                    </button>
                                    <div data-photo class="lx-niche relative aspect-[3/4] overflow-hidden bg-sand ring-1 ring-line/70 transition-transform duration-300 group-active:scale-[0.98]">
                                        @if ($product->image_path)
                                            <img src="{{ $product->imageUrl('sm') }}" alt="" loading="lazy" decoding="async" class="img-fade size-full object-cover">
                                        @else
                                            <span class="grid size-full place-items-center font-display text-7xl font-light text-brand-ink/50">{{ mb_substr($product->name, 0, 1) }}</span>
                                        @endif
                                    </div>
                                    <h3 class="mt-4 font-display text-xl leading-snug font-normal">{{ $product->name }}</h3>
                                    <bdi class="mt-1 block text-sm font-medium tabular-nums text-brand-ink">{{ $shop->formatPrice($product->price) }}</bdi>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                <p x-show="noResults" x-cloak class="mx-auto max-w-2xl px-6 pt-12 text-center text-ink-soft">{{ __('shopinfo.menu.search_empty') }}</p>

                {{-- Categories: a name, a hairline, a count, then the gallery -- --}}
                <div class="mx-auto max-w-2xl px-5 pb-16">
                    @foreach ($categories as $category)
                        <section id="section-{{ $category->id }}" data-section="{{ $category->id }}" class="menu-section pt-14"
                                 aria-labelledby="category-title-{{ $category->id }}">
                            <header class="mb-8" data-reveal>
                                @if ($category->image_path)
                                    <div class="mb-6 aspect-[16/7] overflow-hidden bg-sand">
                                        <img src="{{ $category->imageUrl() }}" alt="" loading="lazy" decoding="async" class="img-fade size-full object-cover">
                                    </div>
                                @endif
                                <div class="flex items-baseline gap-4">
                                    <h2 id="category-title-{{ $category->id }}" class="font-display text-3xl font-light">{{ $category->name }}</h2>
                                    <span class="lx-rule flex-1" aria-hidden="true"></span>
                                    <span class="text-sm tabular-nums text-ink-mute" dir="ltr">{{ $category->products->count() }}</span>
                                </div>
                            </header>

                            <div class="grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6">
                                @foreach ($category->products as $product)
                                    <x-menu.luxe-card :product="$product" :shop="$shop" />
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </main>
        @endif

        {{-- Visit us ------------------------------------------------------ --}}
        @if ($shop->address || $shop->hours || $shop->maps_url || $shop->instagram || $shop->whatsapp_number)
            <section class="mx-auto max-w-2xl px-6 pb-14" aria-labelledby="visit-title" data-reveal>
                <div class="lx-rule" aria-hidden="true"></div>
                <h2 id="visit-title" class="mt-10 font-display text-3xl font-light">{{ __('shopinfo.menu.visit_title') }}</h2>

                <div class="mt-6 space-y-4">
                    @if ($shop->address)
                        <p class="flex items-start gap-3"><x-icon name="pin" class="mt-0.5 size-5 shrink-0 text-brand-ink" /><span class="whitespace-pre-line">{{ $shop->address }}</span></p>
                    @endif
                    @if ($shop->hours)
                        <p class="flex items-start gap-3"><x-icon name="clock" class="mt-0.5 size-5 shrink-0 text-brand-ink" /><span class="whitespace-pre-line">{{ $shop->hours }}</span></p>
                    @endif

                    <div class="flex flex-wrap gap-2 pt-2">
                        @if ($shop->maps_url)
                            <a href="{{ $shop->maps_url }}" target="_blank" rel="noopener" class="btn bg-brand px-5 py-2.5 text-sm text-brand-fg hover:brightness-110"><x-icon name="pin" class="size-4" /> {{ __('shopinfo.menu.directions') }}</a>
                        @endif
                        @if ($shop->whatsapp_number)
                            <a href="https://wa.me/{{ $shop->whatsapp_number }}" target="_blank" rel="noopener" class="btn btn-ghost px-5 py-2.5 text-sm"><x-icon name="whatsapp" class="size-4 text-brand-ink" /> {{ __('shopinfo.menu.whatsapp') }}</a>
                        @endif
                        @if ($shop->instagram)
                            <a href="https://instagram.com/{{ $shop->instagram }}" target="_blank" rel="noopener" class="btn btn-ghost px-5 py-2.5 text-sm"><x-icon name="instagram" class="size-4 text-brand-ink" /> <span dir="ltr">{{ $shop->instagram }}</span></a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- Footer ------------------------------------------------------- --}}
        <footer @class(['border-t border-line pt-10 text-center text-sm text-ink-mute', 'pb-32' => $shop->acceptsOrders(), 'pb-10' => ! $shop->acceptsOrders()])>
            <button type="button" @click="$dispatch('open-review')"
                    class="mx-auto mb-8 flex min-h-11 flex-col items-center gap-2 px-6 py-3 transition hover:text-ink active:scale-[0.98]">
                <span class="flex gap-0.5 text-saffron" aria-hidden="true">
                    @foreach (range(1, 5) as $n)<x-star class="size-5" />@endforeach
                </span>
                <span class="font-display text-xl font-light text-ink">{{ __('app.review.footer_cta') }}</span>
                <span class="text-xs">{{ __('app.review.footer_hint') }}</span>
            </button>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 transition hover:text-ink">
                {{ __('app.menu.powered_by') }}
                <x-logo class="[&_span]:text-base [&_.logo-mark]:size-6" />
            </a>
        </footer>

        @include('menu.partials.product-sheet')

        @if ($shop->acceptsOrders())
            @include('menu.partials.cart')
        @endif
    </div>
    @include('menu.partials.review')
</x-layouts.base>
