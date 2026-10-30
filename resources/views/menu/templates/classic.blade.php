{{--
    Menu template 1 — "Classic"
    Warm, editorial café menu. The shop's brand color drives the accents
    through CSS variables (--brand / --brand-fg / --brand-ink) on <body>.
--}}
@php
    $locales = $shop->contentLocales();
    $description = $shop->tagline ?: __('app.tagline');
    $shareImage = app(\App\Support\ShareImage::class)->url($shop);
    $alternates = collect($locales)->mapWithKeys(fn ($locale) => [$locale => route('menu.show', [$shop, 'lang' => $locale])])->all();

    // Structured data so Google can show the place (address, Instagram, menu link) in results.
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
                body-class="grain"
                style="--brand: {{ $shop->brand_color }}; --brand-fg: {{ $shop->brandForeground() }}; --brand-ink: {{ $shop->brandOnPaper() }};">
    <x-slot:head>
        <x-seo :title="$shop->name" :description="$description" :url="route('menu.show', [$shop, 'lang' => app()->getLocale()])"
               :image="$shareImage" :alternates="$alternates" :json-ld="$jsonLd" />        {{-- Decide before first paint: JS on? first visit this session? --}}
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

    {{-- First-visit curtain --------------------------------------------- --}}
    <div class="splash" aria-hidden="true">
        <div class="flex flex-col items-center">
            <div class="splash-arch arch grid h-48 w-36 place-items-center overflow-hidden bg-brand-fg/12 ring-1 ring-brand-fg/25">
                <div class="splash-logo grid size-full place-items-center">
                    <x-menu.monogram :shop="$shop" size="text-6xl" cover />
                </div>
            </div>
            <div class="mt-7 overflow-hidden px-6 pb-1">
                <p class="splash-name text-center font-display text-3xl font-semibold">{{ $shop->name }}</p>
            </div>
        </div>
        <span class="absolute bottom-[max(2rem,env(safe-area-inset-bottom))] text-xs opacity-60">{{ __('app.menu.skip') }}</span>
    </div>

    <div x-data="menuPage">
        {{-- Hero ---------------------------------------------------------- --}}
        <header class="relative overflow-hidden bg-brand-wash">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="arch absolute -bottom-40 -start-16 h-96 w-64 border border-brand/15"></div>
                <div class="arch absolute -bottom-40 -start-6 h-80 w-44 border border-brand/10"></div>
                <div class="absolute -top-16 -end-12 size-56 rounded-full bg-brand/8"></div>
            </div>

            <div class="relative mx-auto max-w-2xl px-5 pt-5 pb-12">
                @if (count($locales) > 1)
                    {{-- z-20: the hero's animated elements below must not cover the open menu --}}
                    <nav class="hero-in relative z-20 flex justify-end" aria-label="{{ __('app.menu.language') }}">
                        <x-locale-switcher :locales="$locales"
                                           :urls="collect($locales)->mapWithKeys(fn ($locale) => [$locale => route('menu.show', [$shop, 'lang' => $locale])])->all()" />
                    </nav>
                @endif

                <div class="mt-6 flex flex-col items-center text-center">
                    <div class="hero-in arch relative grid h-44 w-34 place-items-center overflow-hidden bg-brand text-brand-fg shadow-[0_30px_60px_-30px_var(--brand)]" style="--i: 1">
                        <div @class(["arch pointer-events-none absolute inset-2.5 border border-brand-fg/25", "hidden" => $shop->logo_path]) aria-hidden="true"></div>
                        <x-menu.monogram :shop="$shop" size="text-6xl" cover />
                    </div>

                    <h1 class="hero-in mt-7 font-display text-4xl leading-tight font-semibold text-balance sm:text-5xl" style="--i: 2">{{ $shop->name }}</h1>

                    @if ($shop->tagline)
                        <p class="hero-in mt-3 max-w-md text-ink-soft text-balance" style="--i: 3">{{ $shop->tagline }}</p>
                    @endif

                    <div class="hero-in ornament mt-7 w-40" style="--i: 4"><x-menu.star /></div>

                    @if ($shop->maps_url || $shop->instagram || $shop->whatsapp_number)
                        <div class="hero-in mt-6 flex flex-wrap items-center justify-center gap-2" style="--i: 5">
                            @if ($shop->maps_url)
                                <a href="{{ $shop->maps_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3.5 py-2 text-sm font-medium ring-1 ring-line transition hover:bg-white active:scale-95">
                                    <x-icon name="pin" class="size-4 text-brand-ink" /> {{ __('shopinfo.menu.directions') }}
                                </a>
                            @endif
                            @if ($shop->instagram)
                                <a href="https://instagram.com/{{ $shop->instagram }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3.5 py-2 text-sm font-medium ring-1 ring-line transition hover:bg-white active:scale-95">
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
            {{-- Nothing published yet ------------------------------------- --}}
            <main class="mx-auto flex max-w-2xl flex-col items-center px-6 py-20 text-center">
                <div class="arch grid h-28 w-20 place-items-end overflow-hidden bg-brand/15">
                    <div class="arch h-20 w-full bg-brand/30"></div>
                </div>
                <h2 class="mt-6 font-display text-3xl font-semibold">{{ __('app.menu.empty_title') }}</h2>
                <p class="mt-2 text-ink-soft">{{ __('app.menu.empty_text') }}</p>
            </main>
        @else
            {{-- Sticky category bar ---------------------------------------- --}}
            <nav x-ref="bar" class="sticky top-0 z-30 border-b border-line/60 bg-paper/85 backdrop-blur-md" aria-label="{{ __('app.menu.sections') }}">
                <div class="mx-auto max-w-2xl">
                    <div class="grid transition-[grid-template-rows] duration-500 ease-(--ease-soft)" :class="compact ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                        <div class="overflow-hidden">
                            <button type="button" @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                                    class="menu-bar-brand flex items-center gap-2.5 px-5 pt-3" :class="compact ? 'opacity-100' : 'opacity-0 -translate-y-2'"
                                    :tabindex="compact ? 0 : -1">
                                <span class="grid size-7 place-items-center overflow-hidden rounded-full bg-brand text-sm text-brand-fg">
                                    <x-menu.monogram :shop="$shop" size="text-sm" padding="p-1" />
                                </span>
                                <span class="truncate font-display text-lg font-semibold">{{ $shop->name }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto px-5 py-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <div x-ref="chips" class="relative flex w-max gap-1">
                            <span x-ref="indicator" class="chip-indicator absolute inset-y-0 left-0 rounded-full bg-ink opacity-0" aria-hidden="true"></span>
                            @foreach ($categories as $category)
                                <button type="button" data-chip="{{ $category->id }}" @click="go('{{ $category->id }}')"
                                        class="relative z-10 rounded-full px-4 py-2 text-sm font-medium whitespace-nowrap transition-colors duration-300"
                                        :class="active === '{{ $category->id }}' ? 'text-paper' : 'text-ink-soft hover:text-ink'"
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
                    <div class="mx-auto max-w-2xl px-5 pt-6" data-search-box>
                        <label class="relative block">
                            <span class="sr-only">{{ __('shopinfo.menu.search_placeholder') }}</span>
                            <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-ink-mute" />
                            <input type="search" x-model="query" @input.debounce.150ms="search()" autocomplete="off"
                                   placeholder="{{ __('shopinfo.menu.search_placeholder') }}"
                                   class="field !rounded-full !bg-white/80 ps-12">
                        </label>
                    </div>
                @endif

                {{-- Featured carousel ------------------------------------- --}}
                @if ($featured->isNotEmpty())
                    <section data-featured x-show="!query.trim()" class="mx-auto max-w-2xl pt-9" aria-labelledby="featured-title">
                        <div class="px-5" data-reveal>
                            <p class="eyebrow !text-brand-ink">{{ __('app.menu.featured_hint') }}</p>
                            <h2 id="featured-title" class="mt-1 font-display text-2xl font-semibold">{{ __('app.menu.featured') }}</h2>
                        </div>
                        <div class="mt-4 flex snap-x snap-mandatory scroll-px-5 gap-4 overflow-x-auto px-5 pb-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            @foreach ($featured as $product)
                                <article data-card data-reveal class="group relative w-[60vw] max-w-56 shrink-0 snap-start">
                                    <button type="button" class="absolute inset-0 z-10" @click="open({{ $product->id }}, $el)">
                                        <span class="sr-only">{{ $product->name }} — {{ __('app.menu.details') }}</span>
                                    </button>
                                    <div data-photo class="arch relative aspect-[4/5] overflow-hidden bg-brand/12 transition-transform duration-300 group-active:scale-[0.97]">
                                        @if ($product->image_path)
                                            <img src="{{ $product->imageUrl('sm') }}" alt="" loading="lazy" decoding="async"
                                                 class="img-fade size-full object-cover transition-transform duration-700 ease-(--ease-soft) group-hover:scale-105">
                                        @else
                                            <span class="grid size-full place-items-center font-display text-6xl text-brand-ink/40">{{ mb_substr($product->name, 0, 1) }}</span>
                                        @endif
                                        <bdi class="absolute bottom-3 start-3 rounded-full bg-paper/90 px-3 py-1 text-sm font-semibold text-brand-ink shadow-sm backdrop-blur">
                                            {{ $shop->formatPrice($product->price) }}
                                        </bdi>
                                    </div>
                                    <h3 class="mt-3 px-1 leading-snug font-semibold">{{ $product->name }}</h3>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Categories ---------------------------------------------- --}}
                <p x-show="noResults" x-cloak class="mx-auto max-w-2xl px-5 pt-12 text-center text-ink-soft">{{ __('shopinfo.menu.search_empty') }}</p>

                <div class="mx-auto max-w-2xl px-4 pb-16 sm:px-5">
                    @foreach ($categories as $category)
                        <section id="section-{{ $category->id }}" data-section="{{ $category->id }}" class="menu-section pt-12"
                                 aria-labelledby="category-title-{{ $category->id }}">
                            <header class="mb-5" data-reveal>
                                @if ($category->image_path)
                                    <div class="mb-5 aspect-[16/7] overflow-hidden rounded-[1.75rem] bg-sand">
                                        <img src="{{ $category->imageUrl() }}" alt="" loading="lazy" decoding="async" class="img-fade size-full object-cover">
                                    </div>
                                @endif
                                <div class="ornament"><x-menu.star /></div>
                                <h2 id="category-title-{{ $category->id }}" class="mt-3 text-center font-display text-3xl font-semibold">{{ $category->name }}</h2>
                            </header>

                            <div class="space-y-3">
                                @foreach ($category->products as $product)
                                    <x-menu.product-card :product="$product" :shop="$shop" />
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </main>
        @endif

        {{-- Visit us ------------------------------------------------------ --}}
        @if ($shop->address || $shop->hours || $shop->maps_url || $shop->instagram || $shop->whatsapp_number)
            <section class="mx-auto max-w-2xl px-4 pb-14 sm:px-5" aria-labelledby="visit-title" data-reveal>
                <div class="ornament"><x-menu.star /></div>
                <h2 id="visit-title" class="mt-3 text-center font-display text-3xl font-semibold">{{ __('shopinfo.menu.visit_title') }}</h2>

                <div class="mt-6 space-y-4 rounded-[1.75rem] bg-white/65 p-5 ring-1 ring-line/70 sm:p-6">
                    @if ($shop->address)
                        <p class="flex items-start gap-3"><x-icon name="pin" class="mt-0.5 size-5 shrink-0 text-brand-ink" /><span class="whitespace-pre-line">{{ $shop->address }}</span></p>
                    @endif
                    @if ($shop->hours)
                        <p class="flex items-start gap-3"><x-icon name="clock" class="mt-0.5 size-5 shrink-0 text-brand-ink" /><span class="whitespace-pre-line">{{ $shop->hours }}</span></p>
                    @endif

                    <div class="flex flex-wrap gap-2 pt-1">
                        @if ($shop->maps_url)
                            <a href="{{ $shop->maps_url }}" target="_blank" rel="noopener" class="btn bg-brand px-5 py-2.5 text-sm text-brand-fg hover:brightness-110"><x-icon name="pin" class="size-4" /> {{ __('shopinfo.menu.directions') }}</a>
                        @endif
                        @if ($shop->whatsapp_number)
                            <a href="https://wa.me/{{ $shop->whatsapp_number }}" target="_blank" rel="noopener" class="btn btn-ghost px-5 py-2.5 text-sm"><x-icon name="whatsapp" class="size-4 text-olive" /> {{ __('shopinfo.menu.whatsapp') }}</a>
                        @endif
                        @if ($shop->instagram)
                            <a href="https://instagram.com/{{ $shop->instagram }}" target="_blank" rel="noopener" class="btn btn-ghost px-5 py-2.5 text-sm"><x-icon name="instagram" class="size-4 text-brand-ink" /> <span dir="ltr">{{ $shop->instagram }}</span></a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- Footer ------------------------------------------------------- --}}
        <footer @class(['border-t border-line/70 pt-10 text-center text-sm text-ink-mute', 'pb-32' => $shop->acceptsOrders(), 'pb-10' => ! $shop->acceptsOrders()])>
            <button type="button" @click="$dispatch('open-review')"
                    class="mx-auto mb-8 flex flex-col items-center gap-2 rounded-3xl px-6 py-4 transition hover:bg-sand/70 active:scale-[0.98]">
                <span class="flex gap-0.5 text-saffron" aria-hidden="true">
                    @foreach (range(1, 5) as $n)<x-star class="size-6" />@endforeach
                </span>
                <span class="font-display text-xl font-semibold text-ink">{{ __('app.review.footer_cta') }}</span>
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
