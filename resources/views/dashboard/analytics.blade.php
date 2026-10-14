<x-layouts.dashboard :title="__('analytics.title')">
    <x-page-header :title="__('analytics.title')" :subtitle="__('analytics.subtitle')" />

    @php
        $tabClass = fn (bool $on) => $on
            ? 'bg-ink text-paper ring-ink'
            : 'bg-white/60 text-ink-soft ring-line hover:bg-paper';
        $peak = max(1, collect($series)->max('menu_view'));
        $hasData = array_sum($totals) > 0;
    @endphp

    {{-- Period + grouping: one button that opens a small popup --}}
    <div class="reveal relative mb-6" style="--i: 1" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
        <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="dialog"
                class="inline-flex min-h-11 items-center gap-2 rounded-full bg-white/70 px-4 text-sm font-medium text-ink ring-1 ring-line transition hover:bg-white active:scale-95">
            <x-icon name="chart" class="size-4 text-clay" />
            <span>{{ __('analytics.range.'.$range) }} · {{ __('analytics.group.'.$group) }}</span>
            <span class="transition-transform duration-300" :class="open && 'rotate-180'"><x-icon name="chevron-down" class="size-3.5 opacity-60" /></span>
        </button>

        <div x-show="open" x-cloak role="dialog" aria-label="{{ __('analytics.filter') }}"
             x-transition:enter="transition duration-200 ease-(--ease-spring)"
             x-transition:enter-start="scale-95 opacity-0"
             x-transition:leave="transition duration-150 ease-in"
             x-transition:leave-end="scale-95 opacity-0"
             class="absolute start-0 top-full z-40 mt-2 w-[min(20rem,calc(100vw-2rem))] space-y-4 rounded-2xl bg-paper p-4 shadow-[0_20px_50px_-20px_rgb(36_26_20/0.45)] ring-1 ring-line ltr:origin-top-left rtl:origin-top-right">
            <div>
                <p class="mb-2 text-xs text-ink-mute">{{ __('analytics.period') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($ranges as $option)
                        <a href="{{ route('analytics.index', ['range' => $option, 'group' => $group]) }}" @if ($option === $range) aria-current="true" @endif
                           class="inline-flex min-h-11 items-center gap-1.5 rounded-full px-4 text-sm font-medium ring-1 transition {{ $tabClass($option === $range) }}">
                            @if ($option === $range) <x-icon name="check" class="size-4" /> @endif
                            {{ __('analytics.range.'.$option) }}
                        </a>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="mb-2 text-xs text-ink-mute">{{ __('analytics.group.label') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($groups as $option)
                        <a href="{{ route('analytics.index', ['range' => $range, 'group' => $option]) }}" @if ($option === $group) aria-current="true" @endif
                           class="inline-flex min-h-11 items-center gap-1.5 rounded-full px-4 text-sm font-medium ring-1 transition {{ $tabClass($option === $group) }}">
                            @if ($option === $group) <x-icon name="check" class="size-4" /> @endif
                            {{ __('analytics.group.'.$option) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if (! $hasData)
        <x-empty-state :title="__('analytics.empty_title')" :text="__('analytics.empty_text')" icon="chart" />
    @else
        {{-- Totals --}}
        <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach (\App\Support\Analytics::EVENTS as $event)
                <div class="reveal card p-4 sm:p-5" style="--i: {{ $loop->iteration + 1 }}">
                    <dt class="text-sm font-medium text-ink-soft">{{ __('analytics.kpi.'.$event) }}</dt>
                    <dd class="mt-2 font-display text-3xl font-semibold tabular-nums sm:text-4xl" dir="ltr">{{ number_format($totals[$event]) }}</dd>
                    <p class="mt-1 text-xs text-ink-mute">{{ __('analytics.kpi_hint.'.$event) }}</p>
                </div>
            @endforeach
        </dl>

        {{-- Trend --}}
        <section class="reveal card mt-6 p-5 sm:p-7" style="--i: 6">
            <h2 class="font-display text-xl font-semibold">{{ __('analytics.trend') }}</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[30rem] text-sm">
                    <thead>
                        <tr class="text-ink-mute">
                            <th scope="col" class="py-2 pe-3 text-start font-medium"></th>
                            <th scope="col" class="py-2 pe-3 text-start font-medium">{{ __('analytics.kpi.menu_view') }}</th>
                            <th scope="col" class="px-2 py-2 text-end font-medium">{{ __('analytics.kpi.product_view') }}</th>
                            <th scope="col" class="px-2 py-2 text-end font-medium">{{ __('analytics.kpi.add_to_cart') }}</th>
                            <th scope="col" class="ps-2 py-2 text-end font-medium">{{ __('analytics.kpi.whatsapp_click') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line/70">
                        @foreach (array_reverse($series) as $entry)
                            <tr>
                                <th scope="row" class="py-2.5 pe-3 text-start font-medium whitespace-nowrap">{{ $entry['label'] }}</th>
                                <td class="py-2.5 pe-3" dir="ltr">
                                    <span class="flex items-center gap-2">
                                        <span class="h-2 min-w-px flex-1 rounded-full bg-olive/80" style="max-width: {{ round($entry['menu_view'] / $peak * 100) }}%"></span>
                                        <span class="w-10 text-end tabular-nums">{{ $entry['menu_view'] }}</span>
                                    </span>
                                </td>
                                <td class="px-2 py-2.5 text-end tabular-nums" dir="ltr">{{ $entry['product_view'] }}</td>
                                <td class="px-2 py-2.5 text-end tabular-nums" dir="ltr">{{ $entry['add_to_cart'] }}</td>
                                <td class="ps-2 py-2.5 text-end tabular-nums" dir="ltr">{{ $entry['whatsapp_click'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Popular products --}}
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            @foreach ([['top_viewed', $topViewed], ['top_added', $topAdded]] as [$key, $list])
                <section class="reveal card p-5 sm:p-7" style="--i: {{ $loop->iteration + 6 }}">
                    <h2 class="font-display text-xl font-semibold">{{ __('analytics.'.$key) }}</h2>
                    @if ($list->isEmpty())
                        <p class="mt-3 text-sm text-ink-mute">{{ __('analytics.empty_list') }}</p>
                    @else
                        <ol class="mt-4 space-y-3">
                            @foreach ($list as $row)
                                <li class="flex items-center gap-3">
                                    <span class="w-5 text-sm text-ink-mute tabular-nums" dir="ltr">{{ $loop->iteration }}</span>
                                    <x-thumb :src="$row['product']->imageUrl('sm')" :label="$row['product']->name" size="h-14 w-12" />
                                    <span class="min-w-0 flex-1 truncate font-medium" dir="auto">{{ $row['product']->name }}</span>
                                    <span class="shrink-0 text-sm text-ink-soft tabular-nums" dir="ltr">{{ __('analytics.times', ['count' => number_format($row['total'])]) }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            @endforeach
        </div>
    @endif

    {{-- Honest labels + privacy --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <aside class="reveal rounded-3xl bg-saffron/10 p-5 ring-1 ring-saffron/30" style="--i: 8">
            <h2 class="font-semibold">{{ __('analytics.honesty_title') }}</h2>
            <p class="mt-1 text-sm text-ink-soft">{{ __('analytics.honesty_text') }}</p>
        </aside>
        <aside class="reveal rounded-3xl bg-olive-soft/60 p-5 ring-1 ring-olive/20" style="--i: 9">
            <h2 class="font-semibold">{{ __('analytics.privacy_title') }}</h2>
            <p class="mt-1 text-sm text-ink-soft">{{ __('analytics.privacy_text') }}</p>
            <p class="mt-2 text-sm text-ink-mute">
                {{ __('analytics.retention.label') }}: <bdi>{{ __('analytics.retention.days', ['days' => $retention]) }}</bdi>
                · <a href="{{ route('settings.edit') }}#analytics" class="text-clay underline-offset-2 hover:underline">{{ __('app.nav.settings') }}</a>
            </p>
        </aside>
    </div>
</x-layouts.dashboard>
