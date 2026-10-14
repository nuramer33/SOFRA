@props(['title' => null])
<x-layouts.base :title="$title" body-class="grain">
    <div class="lg:grid lg:min-h-dvh lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">

        {{-- Form side ------------------------------------------------ --}}
        <main class="flex min-h-dvh flex-col px-5 pt-5 pb-8 sm:px-10 lg:px-16">
            <header class="reveal relative z-30 flex items-center justify-between">
                <a href="{{ route('home') }}" class="rounded-lg"><x-logo /></a>
                <x-locale-switcher />
            </header>

            <div class="flex flex-1 items-center py-10 lg:py-14">
                <div class="mx-auto w-full max-w-md">
                    {{ $slot }}
                </div>
            </div>

            <p class="reveal text-center text-xs text-ink-mute lg:text-start" style="--i: 8">
                © {{ date('Y') }} {{ __('app.brand') }} · {{ __('app.tagline') }}
            </p>
        </main>

        {{-- Brand panel (desktop) — shows owners what they're building --}}
        <aside class="relative m-3 hidden overflow-hidden rounded-[2.5rem] bg-clay-deep text-paper lg:flex lg:flex-col" aria-hidden="true">
            {{-- Decorative arches and a saffron "sun" --}}
            <div class="pointer-events-none absolute inset-0">
                <div class="arch absolute -bottom-24 start-[8%] h-[34rem] w-[22rem] border border-paper/15"></div>
                <div class="arch absolute -bottom-24 start-[14%] h-[30rem] w-[17rem] border border-paper/10"></div>
                <div class="absolute top-[18%] end-[10%] size-40 rounded-full bg-saffron/90 blur-[1px]"></div>
                <div class="grain absolute inset-0 opacity-60"></div>
            </div>

            <div class="relative px-12 pt-14 xl:px-16">
                <p class="reveal eyebrow !text-saffron" style="--i: 2">{{ __('app.brand') }}</p>
                <h2 class="reveal mt-4 max-w-md font-display text-4xl leading-[1.25] font-semibold xl:text-[2.75rem]" style="--i: 3">
                    {{ __('app.panel.title') }}
                </h2>
                <ul class="mt-8 space-y-3">
                    @foreach (__('app.panel.points') as $i => $point)
                        <li class="reveal flex items-center gap-3 text-paper/85" style="--i: {{ 4 + $i }}">
                            <span class="grid size-6 place-items-center rounded-full bg-paper/15"><x-icon name="check" class="size-3.5" /></span>
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Phone mock-up --}}
            <div class="relative mt-auto flex justify-center px-12 pt-12">
                <div class="reveal relative" style="--i: 5">
                    <div class="animate-float relative w-[290px] rounded-t-[2.75rem] border-[10px] border-b-0 border-ink bg-paper px-5 pt-6 pb-10 text-ink shadow-[0_40px_80px_-30px_rgb(0_0_0/0.6)]">
                        <div class="mx-auto mb-5 h-1.5 w-16 rounded-full bg-ink/15"></div>

                        <div class="flex items-center gap-3">
                            <span class="grid size-11 place-items-center rounded-full bg-clay text-paper"><x-icon name="cup" class="size-5" /></span>
                            <div>
                                <p class="font-display text-lg leading-tight font-semibold">{{ __('app.panel.mock_shop') }}</p>
                                <p class="text-xs text-ink-mute">★ 4.9 · 08:00–23:00</p>
                            </div>
                        </div>

                        <div class="mt-5 flex gap-2 text-xs">
                            @foreach (__('app.panel.mock_tabs') as $i => $tab)
                                <span @class(['rounded-full px-3 py-1.5', 'bg-ink text-paper' => $i === 0, 'bg-sand text-ink-soft' => $i > 0])>{{ $tab }}</span>
                            @endforeach
                        </div>

                        @php($plates = [
                            'radial-gradient(circle at 38% 36%, #9a6a48 0 18%, #3b2418 38%, #f1e6d4 41% 58%, #e2d3bb 60%)',
                            'radial-gradient(circle at 40% 40%, #f4b555 0 20%, #c8641e 44%, #f3e7d3 47% 60%, #e4d6c0 62%)',
                            'radial-gradient(circle at 40% 40%, #fbe6bd 0 14%, #d9a85b 40%, #efe5d5 43% 60%, #e1d2b9 62%)',
                        ])
                        <ul class="mt-5 space-y-3">
                            @foreach (__('app.panel.mock_items') as $i => $item)
                                <li class="flex items-center gap-3 rounded-2xl bg-white/70 p-2.5 ring-1 ring-line/70">
                                    <span class="size-14 shrink-0 rounded-full" style="background: {{ $plates[$i] }}"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium">{{ $item['name'] }}</span>
                                        <span class="block truncate text-xs text-ink-mute">{{ $item['note'] }}</span>
                                    </span>
                                    <span class="text-sm font-semibold text-clay" dir="ltr">₺{{ $item['price'] }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-5 flex items-center justify-center gap-2 rounded-full bg-olive py-3 text-sm font-medium text-paper">
                            <x-icon name="whatsapp" class="size-4" /> {{ __('app.panel.mock_order') }}
                        </div>
                    </div>

                    {{-- Incoming order bubble --}}
                    <div class="animate-float absolute -end-32 top-16 w-56 rounded-2xl bg-paper p-3.5 text-ink shadow-xl ring-1 ring-line [--tilt:-3deg] [animation-delay:-2.5s]">
                        <div class="flex items-center gap-2 text-xs font-medium text-olive">
                            <span class="relative flex size-2"><span class="absolute inset-0 animate-ping rounded-full bg-olive/60"></span><span class="size-2 rounded-full bg-olive"></span></span>
                            {{ __('app.panel.mock_toast') }}
                        </div>
                        <p class="mt-1.5 text-sm">{{ __('app.panel.mock_toast_items') }}</p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.base>
