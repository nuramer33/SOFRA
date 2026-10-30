{{-- Marketing site: sells Sofra to café, restaurant and small-shop owners. --}}
@php
    $t = fn (string $key, array $replace = []) => __("app.landing.{$key}", $replace);
    $items = __('app.panel.mock_items');
    $start = auth()->check() ? auth()->user()->homeUrl() : route('register');
    $startLabel = auth()->check() ? $t('nav.dashboard') : $t('nav.start');
    $plates = [
        'radial-gradient(circle at 38% 36%, #9a6a48 0 18%, #3b2418 38%, #f1e6d4 41% 58%, #e2d3bb 60%)',
        'radial-gradient(circle at 40% 40%, #f4b555 0 20%, #c8641e 44%, #f3e7d3 47% 60%, #e4d6c0 62%)',
        'radial-gradient(circle at 40% 40%, #fbe6bd 0 14%, #d9a85b 40%, #efe5d5 43% 60%, #e1d2b9 62%)',
    ];
    $kinds = __('app.landing.kinds');
    // Structured data: tells Google what Sofra is (and its price, when set).
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => __('app.brand'),
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => $t('hero.subtitle'),
        'url' => route('home'),
        'inLanguage' => \App\Support\Locales::codes(),
    ];
    $jsonLd['offers'] = ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'];
@endphp
<x-layouts.base :exact-title="__('app.brand').' — '.__('app.tagline')" :scripts="['resources/js/landing.js']" body-class="grain landing-olive overflow-x-clip">
    <x-slot:head>
        <x-seo :title="__('app.brand').' — '.__('app.tagline')" :description="$t('hero.subtitle')" :url="route('home')" :json-ld="$jsonLd" />        <script>document.documentElement.classList.add('js');</script>
        <link rel="manifest" href="/manifest.webmanifest">
    </x-slot:head>

    <div class="scroll-progress" aria-hidden="true"></div>

    {{-- Navigation ------------------------------------------------------- --}}
    <header x-data="landingNav" class="sticky top-0 z-40 transition-[background-color,box-shadow] duration-300"
            :class="scrolled ? 'bg-paper/85 shadow-[0_1px_0_var(--color-line)] backdrop-blur-md' : ''">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-5 py-3.5 sm:px-8">
            <a href="{{ route('home') }}"><x-logo /></a>
            <nav class="hidden items-center gap-1 text-sm font-medium text-ink-soft md:flex">
                @foreach (['how', 'features', 'pricing', 'faq'] as $anchor)
                    <a href="#{{ $anchor }}" class="rounded-full px-3.5 py-2 transition hover:bg-sand hover:text-ink">{{ $t('nav.'.$anchor) }}</a>
                @endforeach
            </nav>
            <div class="flex items-center gap-2">
                <x-locale-switcher compact />
                @guest
                    <a href="{{ route('login') }}" class="hidden rounded-full px-3.5 py-2 text-sm font-medium text-ink-soft transition hover:text-ink sm:block">{{ $t('nav.login') }}</a>
                @endguest
                <a href="{{ $start }}" class="btn btn-primary px-4 py-2.5 text-sm whitespace-nowrap">{{ $startLabel }}</a>
            </div>
        </div>
    </header>

    <main>
        {{-- Hero ---------------------------------------------------------- --}}
        <section class="relative">
            <div class="hero-bg pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                <div class="blob absolute -top-24 end-[-6rem] size-[28rem] rounded-full bg-saffron/35 blur-3xl"></div>
                <div class="blob blob-2 absolute top-1/3 start-[-8rem] size-[26rem] rounded-full bg-olive/30 blur-3xl"></div>
                <div class="dots absolute inset-0 opacity-40"></div>
                <x-olive-branch class="leaf bottom-0 end-[-2rem] h-[20rem] text-olive/50 sm:end-[1%] sm:h-[30rem]" style="--r: 8deg; --d: 0" />
                <x-olive-branch class="leaf top-0 start-[-3rem] hidden h-64 text-olive/40 sm:block lg:start-[40%] lg:h-72" style="--r: -170deg; --d: 3; transform-origin: 50% 0" :olives="false" />
                @foreach ([['top-[18%] start-[46%]', 14, 0], ['top-[62%] start-[8%]', 10, 2], ['top-[30%] end-[8%]', 12, 4], ['top-[10%] start-[22%]', 8, 3]] as [$pos, $size, $delay])
                    <span class="bob absolute {{ $pos }} rounded-full bg-clay-deep/35" style="width: {{ $size }}px; height: {{ $size * 1.25 }}px; --d: {{ $delay }}"></span>
                @endforeach
            </div>
            <div class="mx-auto grid max-w-6xl items-center gap-14 px-5 pt-10 pb-20 sm:px-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:pt-16 lg:pb-28">
                <div class="text-center lg:text-start">
                    <h1 class="font-display text-[2.6rem] leading-[1.15] font-semibold text-balance sm:text-6xl lg:text-[4.1rem]">
                        @foreach (explode(' ', $t('hero.title')) as $i => $word)
                            <span class="word-mask"><span style="--w: {{ $i }}">{{ $word }}</span></span>
                        @endforeach
                    </h1>
                    <p class="hero-in mx-auto mt-6 max-w-xl text-lg leading-relaxed text-ink-soft lg:mx-0" style="--i: 5">{{ $t('hero.subtitle') }}</p>
                    <div class="hero-in mt-9 flex flex-col items-center gap-3 sm:flex-row sm:justify-center lg:justify-start" style="--i: 6">
                        <a href="{{ $start }}" class="btn btn-primary btn-shine group px-7 py-4 text-lg">
                            {{ $startLabel }}
                            <x-icon name="arrow" class="flip-rtl size-4 transition-transform duration-300 group-hover:translate-x-1 rtl:group-hover:-translate-x-1" />
                        </a>
                        @if ($demoUrl)
                            <a href="{{ $demoUrl }}" target="_blank" rel="noopener" class="btn btn-ghost px-6 py-4">
                                <x-icon name="cup" class="size-5 text-clay" /> {{ $t('hero.demo') }}
                            </a>
                        @endif
                    </div>
                    <p class="hero-in mt-5 text-sm text-ink-mute" style="--i: 7">{{ $t('hero.note') }}</p>
                </div>

                {{-- The phone story --}}
                <div class="hero-in relative mx-auto" style="--i: 4">
                    <div class="story tilt relative" data-tilt>
                        <div class="relative h-[560px] w-[272px] overflow-hidden rounded-[2.6rem] border-[9px] border-ink bg-paper shadow-[0_50px_100px_-40px_rgb(36_26_20/0.55)]" aria-hidden="true">
                            {{-- Screen 1: the menu --}}
                            <div class="st-menu absolute inset-0 px-4 pt-6">
                                <div class="mx-auto mb-4 h-1.5 w-14 rounded-full bg-ink/15"></div>
                                <div class="flex items-center gap-2.5">
                                    <span class="grid size-10 place-items-center rounded-full bg-clay text-paper"><x-icon name="cup" class="size-5" /></span>
                                    <span class="font-display text-lg font-semibold">{{ __('app.panel.mock_shop') }}</span>
                                </div>
                                <div class="mt-4 flex gap-1.5 text-[11px]">
                                    @foreach (__('app.panel.mock_tabs') as $i => $tab)
                                        <span @class(['rounded-full px-3 py-1.5', 'bg-ink text-paper' => $i === 0, 'bg-sand text-ink-soft' => $i > 0])>{{ $tab }}</span>
                                    @endforeach
                                </div>
                                <ul class="mt-4 space-y-2.5">
                                    @foreach ($items as $i => $item)
                                        <li class="relative flex items-center gap-2.5 rounded-2xl bg-white/80 p-2 ring-1 ring-line/70">
                                            <span class="size-14 shrink-0 rounded-full" style="background: {{ $plates[$i] }}"></span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[13px] font-medium">{{ $item['name'] }}</span>
                                                <span class="block text-xs font-semibold text-clay" dir="ltr">{{ $item['price'] }} ₺</span>
                                            </span>
                                            <span class="relative grid size-8 shrink-0 place-items-center rounded-full bg-clay text-sm font-semibold text-paper">
                                                @if ($i === 1)
                                                    <span class="st-tap1 absolute inset-[-6px] rounded-full border-2 border-clay"></span>
                                                    <span class="st-plus absolute">+</span>
                                                    <span class="st-count absolute">1</span>
                                                    <span class="st-dot absolute size-3 rounded-full bg-clay shadow" style="--dx: -96px; --dy: 300px"></span>
                                                @else
                                                    +
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="st-bar absolute inset-x-3 bottom-4 flex items-center gap-2 rounded-full bg-ink py-2 ps-2 pe-4 text-[13px] text-paper">
                                    <span class="st-tap2 absolute inset-[-5px] rounded-full border-2 border-ink/60"></span>
                                    <span class="grid size-7 place-items-center rounded-full bg-clay text-xs font-semibold">1</span>
                                    {{ __('app.cart.view') }}
                                    <span class="ms-auto font-semibold" dir="ltr">{{ $items[1]['price'] }} ₺</span>
                                </div>
                            </div>

                            {{-- Screen 2: WhatsApp --}}
                            <div class="st-chat absolute inset-0 flex flex-col bg-[#efeae2]">
                                <div class="flex items-center gap-2.5 bg-[#075e54] px-3 pt-7 pb-3 text-white">
                                    <span class="grid size-8 place-items-center rounded-full bg-clay text-xs"><x-icon name="cup" class="size-4" /></span>
                                    <span>
                                        <span class="block text-[13px] font-semibold">{{ __('app.panel.mock_shop') }}</span>
                                        <span class="block text-[10px] opacity-80">{{ $t('story.online') }}</span>
                                    </span>
                                </div>
                                <div class="flex-1 p-3">
                                    <div class="st-bubble ms-auto w-[88%] rounded-2xl rounded-se-sm bg-[#d9fdd3] p-3 text-[12px] leading-relaxed shadow-sm">
                                        <p class="font-semibold">🧾 {{ $t('story.title') }} · <bdi dir="ltr">#K7Q2</bdi></p>
                                        <p class="mt-1.5">1 × {{ $items[1]['name'] }} — <bdi>{{ $items[1]['price'] }} ₺</bdi></p>
                                        <p class="mt-1.5 font-semibold">{{ $t('story.total') }}: <bdi>{{ $items[1]['price'] }} ₺</bdi></p>
                                        <p class="mt-1.5">🍽 {{ $t('story.table') }}</p>
                                        <p class="mt-1 flex items-center justify-end gap-1 text-[10px] text-ink-mute">
                                            12:04 <span class="st-ticks font-bold">✓✓</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="m-2 rounded-full bg-white px-4 py-2.5 text-[11px] text-ink-mute">…</div>
                            </div>
                        </div>

                        {{-- Notification next to the phone --}}
                        {{-- Phones: over the empty lower chat area; wider screens: beside the phone --}}
                        <div class="st-toast absolute -end-6 bottom-24 w-48 rounded-2xl bg-paper p-3 shadow-xl ring-1 ring-line sm:top-28 sm:bottom-auto sm:-end-24" aria-hidden="true">
                            <p class="flex items-center gap-2 text-xs font-semibold text-olive">
                                <span class="relative flex size-2"><span class="absolute inset-0 animate-ping rounded-full bg-olive/60"></span><span class="size-2 rounded-full bg-olive"></span></span>
                                {{ $t('story.toast') }}
                            </p>
                            <p class="mt-1 text-sm"><bdi dir="ltr">#K7Q2</bdi> · <bdi>{{ $items[1]['price'] }} ₺</bdi></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Who it's for (marquee) ------------------------------------- --}}
        <section class="marquee overflow-hidden bg-clay py-5 text-paper" aria-label="{{ $t('kinds_label') }}">
            <div class="marquee-track flex w-max gap-10">
                @foreach ([1, 2] as $copy)
                    <ul class="flex shrink-0 items-center gap-10" @if ($copy === 2) aria-hidden="true" @endif>
                        @foreach ($kinds as $kind)
                            <li class="flex items-center gap-10 font-display text-2xl whitespace-nowrap text-paper/90">
                                {{ $kind }} <x-menu.star class="size-3 text-saffron" />
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </section>

        {{-- How it works ------------------------------------------------ --}}
        <section id="how" class="tint-band scroll-mt-20 py-24 lg:py-32"><div class="mx-auto max-w-6xl px-5 sm:px-8">
            <div class="mx-auto max-w-2xl text-center" data-reveal>
                <p class="eyebrow">{{ $t('how.eyebrow') }}</p>
                <h2 class="mt-3 font-display text-4xl font-semibold text-balance sm:text-5xl">{{ $t('how.title') }}</h2>
            </div>
            <div class="relative mt-16" data-reveal>
                <div class="draw-line absolute inset-x-[16%] top-8 hidden h-px bg-linear-to-r from-clay/10 via-clay/50 to-clay/10 md:block" aria-hidden="true"></div>
                <ol class="grid gap-10 md:grid-cols-3">
                    @foreach (__('app.landing.how.steps') as $i => $step)
                        <li class="step-card relative text-center" data-reveal>
                            <span class="arch mx-auto grid h-16 w-13 place-items-center bg-clay font-display text-2xl text-paper shadow-[0_14px_30px_-14px_var(--color-clay)]">{{ $i + 1 }}</span>
                            <h3 class="mt-6 font-display text-2xl font-semibold">{{ $step['title'] }}</h3>
                            <p class="mx-auto mt-2 max-w-xs text-ink-soft">{{ $step['text'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
            </div>
        </section>

        {{-- Features (bento) ------------------------------------------- --}}
        <section id="features" class="olive-night scroll-mt-20 py-24 text-paper lg:py-32">
            <div class="mx-auto max-w-6xl px-5 sm:px-8">
                <div class="max-w-2xl" data-reveal>
                    <p class="eyebrow !text-saffron">{{ $t('features.eyebrow') }}</p>
                    <h2 class="mt-3 font-display text-4xl font-semibold text-balance sm:text-5xl">{{ $t('features.title') }}</h2>
                </div>

                <div class="mt-14 grid gap-4 md:grid-cols-6">
                    {{-- Languages: one name cycling through ar / tr / en --}}
                    <article data-reveal class="rounded-[2rem] bg-paper/[0.06] p-7 ring-1 ring-paper/10 md:col-span-3"
                             x-data="languageCycle(@js(collect(['ar', 'tr', 'en'])->map(fn ($l) => ['text' => __('app.panel.mock_items', [], $l)[0]['name'], 'dir' => \App\Support\Locales::dir($l), 'lang' => $l, 'label' => \App\Support\Locales::name($l)])->all()))">
                        <h3 class="font-display text-2xl font-semibold">{{ $t('features.languages.title') }}</h3>
                        <p class="mt-2 text-paper/70">{{ $t('features.languages.text') }}</p>
                        <div class="mt-7 flex h-24 items-center rounded-2xl bg-paper px-5 text-ink">
                            <template x-for="(name, i) in names" :key="name.lang">
                                <div x-show="index === i" x-transition:enter="transition duration-500 ease-(--ease-soft)" x-transition:enter-start="opacity-0 translate-y-3"
                                     class="flex w-full items-center justify-between gap-4" :dir="name.dir" :lang="name.lang">
                                    <span class="font-display text-2xl font-semibold" x-text="name.text"></span>
                                    <span class="rounded-full bg-sand px-3 py-1 text-xs text-ink-soft" x-text="name.label"></span>
                                </div>
                            </template>
                        </div>
                    </article>

                    {{-- Brand color: tap a swatch, the card recolors --}}
                    <article data-reveal class="rounded-[2rem] bg-paper/[0.06] p-7 ring-1 ring-paper/10 md:col-span-3"
                             x-data="brandDemo(@js(config('sofra.brand_presets')))">
                        <h3 class="font-display text-2xl font-semibold">{{ $t('features.brand.title') }}</h3>
                        <p class="mt-2 text-paper/70">{{ $t('features.brand.text') }}</p>
                        <div class="mt-7 flex flex-wrap items-center gap-5">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="c in colors" :key="c">
                                    <button type="button" @click="color = c" class="size-8 rounded-full ring-2 ring-offset-2 ring-offset-ink transition hover:scale-110"
                                            :class="color === c ? 'ring-paper' : 'ring-transparent'" :style="{ backgroundColor: c }" :aria-label="c"></button>
                                </template>
                            </div>
                            <div class="flex flex-1 items-center gap-3 rounded-2xl bg-paper p-3 text-ink">
                                <span class="arch grid h-12 w-10 place-items-center font-display text-lg text-white transition-colors duration-500" :style="{ backgroundColor: color }">{{ mb_substr(__('app.panel.mock_shop'), 0, 1) }}</span>
                                <span class="flex-1 text-sm font-medium">{{ $items[0]['name'] }}</span>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold text-white transition-colors duration-500" :style="{ backgroundColor: color }" dir="ltr">{{ $items[0]['price'] }} ₺</span>
                            </div>
                        </div>
                    </article>

                    {{-- Sold out toggle --}}
                    <article data-reveal class="rounded-[2rem] bg-paper/[0.06] p-7 ring-1 ring-paper/10 md:col-span-2" x-data="{ on: true }">
                        <h3 class="font-display text-xl font-semibold">{{ $t('features.soldout.title') }}</h3>
                        <p class="mt-2 text-sm text-paper/70">{{ $t('features.soldout.text') }}</p>
                        <div class="mt-6 flex items-center gap-3 rounded-2xl bg-paper p-3 text-ink">
                            <span class="size-11 shrink-0 rounded-full transition duration-500" :class="!on && 'grayscale opacity-50'" style="background: {{ $plates[1] }}"></span>
                            <span class="flex-1 text-sm font-medium" :class="!on && 'text-ink-mute'">{{ $items[1]['name'] }}</span>
                            <button type="button" role="switch" :aria-checked="on.toString()" @click="on = !on" aria-label="{{ $t('features.soldout.title') }}"
                                    class="relative h-7 w-12 rounded-full transition-colors duration-300" :class="on ? 'bg-olive' : 'bg-sand-deep'">
                                <span class="absolute top-1 start-1 size-5 rounded-full bg-white shadow transition-transform duration-300 ease-(--ease-spring)" :class="on && 'translate-x-5 rtl:-translate-x-5'"></span>
                            </button>
                        </div>
                    </article>

                    {{-- WhatsApp, no commission --}}
                    <article data-reveal class="rounded-[2rem] bg-[#0f7d43] p-7 md:col-span-2">
                        <x-icon name="whatsapp" class="size-9" />
                        <h3 class="mt-5 font-display text-xl font-semibold">{{ $t('features.whatsapp.title') }}</h3>
                        <p class="mt-2 text-sm text-white/80">{{ $t('features.whatsapp.text') }}</p>
                    </article>

                    {{-- QR (a real one: opens the demo menu) --}}
                    <article data-reveal class="flex items-center gap-5 rounded-[2rem] bg-paper p-7 text-ink md:col-span-2">
                        <div class="min-w-0 flex-1">
                            <h3 class="font-display text-xl font-semibold">{{ $t('features.qr.title') }}</h3>
                            <p class="mt-2 text-sm text-ink-soft">{{ $t('features.qr.text') }}</p>
                        </div>
                        <div class="w-24 shrink-0 [&>svg]:h-auto [&>svg]:w-full" title="{{ $t('features.qr.scan') }}">{!! $qr->svg() !!}</div>
                    </article>

                    {{-- Fast --}}
                    <article data-reveal class="rounded-[2rem] bg-paper/[0.06] p-7 ring-1 ring-paper/10 md:col-span-6 md:flex md:items-center md:gap-10">
                        <div class="flex shrink-0 gap-2" aria-hidden="true">
                            @foreach (['w-16', 'w-24', 'w-10'] as $w)
                                <span class="{{ $w }} h-2 rounded-full bg-saffron/80"></span>
                            @endforeach
                        </div>
                        <div class="mt-5 md:mt-0">
                            <h3 class="font-display text-xl font-semibold">{{ $t('features.fast.title') }}</h3>
                            <p class="mt-2 text-sm text-paper/70">{{ $t('features.fast.text') }}</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- The order message ------------------------------------------ --}}
        <section class="mx-auto grid max-w-6xl items-center gap-14 px-5 py-24 sm:px-8 lg:grid-cols-2 lg:py-32">
            <div data-reveal>
                <p class="eyebrow">{{ $t('order.eyebrow') }}</p>
                <h2 class="mt-3 font-display text-4xl font-semibold text-balance sm:text-5xl">{{ $t('order.title') }}</h2>
                <p class="mt-5 text-lg leading-relaxed text-ink-soft">{{ $t('order.text') }}</p>
                <ul class="mt-7 space-y-3">
                    @foreach (__('app.landing.order.points') as $point)
                        <li class="flex items-start gap-3"><span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-olive-soft text-olive"><x-icon name="check" class="size-3.5" /></span> {{ $point }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-[2rem] bg-[#efeae2] p-5 shadow-[0_40px_80px_-40px_rgb(36_26_20/0.4)] sm:p-7">
                <div class="ms-auto max-w-sm space-y-1.5 rounded-2xl rounded-se-sm bg-[#d9fdd3] p-4 text-[15px] leading-relaxed shadow-sm">
                    @foreach (__('app.landing.order.message') as $i => $line)
                        <p data-reveal @class(['font-semibold' => in_array($i, [0, 4])])>{!! $line === '' ? '&nbsp;' : str_replace('#K7Q2', '<bdi dir="ltr">#K7Q2</bdi>', e($line)) !!}</p>
                    @endforeach
                    <p class="flex justify-end gap-1 pt-1 text-xs text-ink-mute">12:04 <span class="font-bold text-[#34b7f1]">✓✓</span></p>
                </div>
            </div>
        </section>

        {{-- Get the app ---------------------------------------------------- --}}
        <section id="app" class="scroll-mt-20 px-5 pb-24 sm:px-8 lg:pb-32">
            <div data-reveal class="mx-auto flex max-w-3xl flex-col items-center gap-6 rounded-[2.5rem] bg-clay-soft p-8 text-center sm:flex-row sm:p-10 sm:text-start">
                <span class="grid size-20 shrink-0 place-items-center rounded-3xl bg-clay text-paper"><x-icon name="download" class="size-9" /></span>
                <div class="flex-1">
                    <p class="eyebrow">{{ $t('install.eyebrow') }}</p>
                    <h2 class="mt-1 font-display text-3xl font-semibold text-balance">{{ $t('install.title') }}</h2>
                    <p class="mt-2 text-ink-soft">{{ $t('install.text') }}</p>
                    <button type="button" data-install class="btn btn-primary mt-5 px-6 py-3 whitespace-nowrap"><x-icon name="download" class="size-5" /> {{ $t('install.button') }}</button>
                    <p data-install-hint class="mt-3 text-sm text-ink-mute" hidden>{{ $t('install.android') }}<br>{{ $t('install.ios') }}</p>
                    <p data-install-done class="mt-4 text-sm font-semibold text-olive" hidden>{{ $t('install.done') }}</p>
                </div>
            </div>
        </section>

        {{-- Pricing ------------------------------------------------------- --}}
        <section id="pricing" class="scroll-mt-20 px-5 pb-24 sm:px-8 lg:pb-32">
            <div data-reveal class="relative mx-auto max-w-3xl overflow-hidden rounded-[2.5rem] olive-night p-8 text-paper sm:p-12">
                <div class="arch drift pointer-events-none absolute -bottom-24 -end-10 h-80 w-56 border border-paper/15" aria-hidden="true"></div>
                <p class="eyebrow !text-saffron">{{ $t('pricing.eyebrow') }}</p>
                <h2 class="mt-3 max-w-lg font-display text-4xl font-semibold text-balance sm:text-5xl">{{ $t('pricing.title') }}</h2>
                <div class="mt-8 flex flex-wrap items-end gap-x-4 gap-y-2">
                    <span class="font-display text-6xl font-semibold">{{ $t('pricing.free') }}</span>
                </div>
                <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                    @foreach (__('app.landing.pricing.includes') as $feature)
                        <li class="flex items-center gap-3"><span class="grid size-6 place-items-center rounded-full bg-paper/15"><x-icon name="check" class="size-3.5" /></span> {{ $feature }}</li>
                    @endforeach
                </ul>
                <a href="{{ $start }}" class="btn mt-10 bg-paper px-7 py-4 text-lg text-ink hover:bg-white">{{ auth()->check() ? $startLabel : $t('pricing.cta') }}</a>
                <p class="mt-4 text-sm text-paper/70">{{ $t('pricing.note') }}</p>
            </div>
        </section>

        {{-- FAQ ----------------------------------------------------------- --}}
        <section id="faq" class="relative mx-auto max-w-3xl scroll-mt-20 px-5 pb-24 sm:px-8 lg:pb-32">
            <x-olive-branch class="leaf pointer-events-none absolute -top-10 -start-24 -z-10 hidden h-80 text-olive/30 lg:block" style="--r: 20deg" :olives="false" />
            <x-olive-branch class="leaf pointer-events-none absolute -end-24 bottom-10 -z-10 hidden h-80 text-saffron/40 lg:block" style="--r: -15deg; --d: 2" :olives="false" />
            <h2 data-reveal class="text-center font-display text-4xl font-semibold">{{ $t('faq.title') }}</h2>
            <div class="mt-10 space-y-3">
                @foreach (__('app.landing.faq.items') as $i => $item)
                    <div data-reveal class="card" x-data="{ open: {{ $i === 0 ? 'true' : 'false' }} }">
                        <h3>
                            <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="faq-{{ $i }}"
                                    class="flex w-full items-center justify-between gap-4 p-5 text-start font-semibold sm:p-6">
                                {{ $item['q'] }}
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-sand transition-transform duration-300" :class="open && 'rotate-45'">
                                    <x-icon name="plus" class="size-4" />
                                </span>
                            </button>
                        </h3>
                        <div id="faq-{{ $i }}" @class(['faq-panel', 'is-open' => $i === 0]) :class="open && 'is-open'">
                            <div class="overflow-hidden">
                                <p class="px-5 pb-6 leading-relaxed text-ink-soft sm:px-6">{{ $item['a'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Final call to action ---------------------------------------- --}}
        <section class="px-5 pb-10 sm:px-8">
            <div data-reveal class="relative mx-auto max-w-6xl overflow-hidden rounded-[2.5rem] olive-night px-6 py-20 text-center text-paper">
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="arch drift absolute -bottom-28 start-[10%] h-72 w-48 border border-paper/10"></div>
                    <div class="arch drift absolute -bottom-36 end-[12%] h-80 w-56 bg-clay/30 [animation-delay:-6s]"></div>
                </div>
                <span class="logo-mark relative mx-auto mb-8 block size-28 bg-paper sm:size-36" style="--mark: url('{{ asset('icons/mark-thin.png') }}')" aria-hidden="true"></span>
                <h2 class="relative font-display text-4xl font-semibold text-balance sm:text-6xl">{{ $t('final.title') }}</h2>
                <p class="relative mt-4 text-lg text-paper/75">{{ $t('final.text') }}</p>
                <a href="{{ $start }}" class="btn btn-primary relative mt-9 px-8 py-4 text-lg">{{ auth()->check() ? $startLabel : $t('final.cta') }}</a>
            </div>
        </section>
    </main>

    <footer class="mx-auto max-w-6xl px-5 py-10 text-sm text-ink-mute sm:px-8">
        <div class="flex flex-col items-center justify-between gap-5 sm:flex-row">
            <x-logo />
            <nav class="flex flex-wrap justify-center gap-x-6 gap-y-2" aria-label="{{ __('legal.footer.contact') }}">
                <a href="{{ route('legal.privacy') }}" class="transition hover:text-clay">{{ __('legal.footer.privacy') }}</a>
                <a href="{{ route('legal.terms') }}" class="transition hover:text-clay">{{ __('legal.footer.terms') }}</a>
                @if (config('sofra.billing.support_whatsapp'))
                    <a href="https://wa.me/{{ config('sofra.billing.support_whatsapp') }}" target="_blank" rel="noopener" class="transition hover:text-clay">{{ __('legal.footer.contact') }}</a>
                @elseif (config('sofra.billing.support_email'))
                    <a href="mailto:{{ config('sofra.billing.support_email') }}" class="transition hover:text-clay">{{ __('legal.footer.contact') }}</a>
                @endif
                @if (config('sofra.billing.support_instagram'))
                    <a href="https://instagram.com/{{ config('sofra.billing.support_instagram') }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 transition hover:text-clay" aria-label="Instagram">
                        <x-icon name="instagram" class="size-4" /> <span dir="ltr">{{ '@'.config('sofra.billing.support_instagram') }}</span>
                    </a>
                @endif
            </nav>
            <p>© {{ date('Y') }} {{ __('app.brand') }}</p>
        </div>
        <p class="mt-6 text-center">{{ $t('footer.made') }}</p>
    </footer>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        }

        (() => {
            const buttons = document.querySelectorAll('[data-install]');
            const hint = document.querySelector('[data-install-hint]');
            const done = document.querySelector('[data-install-done]');
            let deferredPrompt = null;

            const markInstalled = () => {
                buttons.forEach((button) => { button.hidden = true; });
                hint.hidden = true;
                done.hidden = false;
            };

            if (matchMedia('(display-mode: standalone)').matches || navigator.standalone) {
                markInstalled();
                return;
            }

            window.addEventListener('beforeinstallprompt', (event) => {
                event.preventDefault();
                deferredPrompt = event;
            });
            window.addEventListener('appinstalled', markInstalled);

            buttons.forEach((button) => button.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    await deferredPrompt.userChoice;
                    deferredPrompt = null;
                } else {
                    hint.hidden = false;
                }
            }));
        })();
    </script>
</x-layouts.base>