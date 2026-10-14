@php
    $locale = app()->getLocale();
    // Weeks start on Saturday for Arabic readers, Monday elsewhere. 0 = Sunday.
    $order = $locale === 'ar' ? [6, 0, 1, 2, 3, 4, 5] : [1, 2, 3, 4, 5, 6, 0];
    $saved = $shop->order_hours ?? [];
    $defaults = ['open' => true, 'from' => '09:00', 'to' => '23:00'];
    $days = collect(range(0, 6))->mapWithKeys(fn ($day) => [$day => [
        'open' => (bool) old("hours.{$day}.open", $saved[(string) $day]['open'] ?? $defaults['open']),
        'from' => old("hours.{$day}.from", $saved[(string) $day]['from'] ?? $defaults['from']),
        'to' => old("hours.{$day}.to", $saved[(string) $day]['to'] ?? $defaults['to']),
    ]])->all();
    $timeInput = 'field w-32 py-2.5 text-center tabular-nums';
@endphp
<x-layouts.dashboard :title="__('quick.hours.title')">
    <x-page-header :title="__('quick.hours.title')" :subtitle="__('quick.hours.subtitle')" :back="route('today')" />

    @if ($errors->any())
        <div role="alert" class="reveal mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger ring-1 ring-danger/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('ordering.update') }}" class="reveal space-y-4" style="--i: 1"
          x-data="{ busy: false, paused: @js((bool) old('orders_paused', $shop->orders_paused)), enabled: @js((bool) old('hours_enabled', $shop->order_hours !== null)), days: @js($days) }"
          @submit="busy = true">
        @csrf
        @method('PUT')

        {{-- Pause --}}
        <section class="card p-5 sm:p-7">
            <label class="flex cursor-pointer items-start justify-between gap-4">
                <span>
                    <span class="block font-display text-lg font-semibold">{{ __('quick.pause.stop_label') }}</span>
                    <span class="mt-0.5 block text-sm text-ink-soft">{{ __('quick.pause.hint') }}</span>
                </span>
                <input type="hidden" name="orders_paused" :value="paused ? 1 : 0">
                <input type="checkbox" role="switch" x-model="paused" class="peer sr-only">
                <span aria-hidden="true" class="relative mt-0.5 h-7 w-12 shrink-0 rounded-full transition-colors duration-300" :class="paused ? 'bg-clay' : 'bg-sand-deep'">
                    <span class="absolute top-1 start-1 size-5 rounded-full bg-white shadow transition-transform duration-300 ease-(--ease-spring)" :class="paused && 'translate-x-5 rtl:-translate-x-5'"></span>
                </span>
            </label>
        </section>

        {{-- Weekly hours --}}
        <section class="card space-y-5 p-5 sm:p-7">
            <label class="flex cursor-pointer items-start justify-between gap-4">
                <span>
                    <span class="block font-display text-lg font-semibold">{{ __('quick.hours.limit_label') }}</span>
                    <span class="mt-0.5 block text-sm text-ink-soft">{{ __('quick.hours.limit_hint') }}</span>
                </span>
                <input type="hidden" name="hours_enabled" :value="enabled ? 1 : 0">
                <input type="checkbox" role="switch" x-model="enabled" class="peer sr-only">
                <span aria-hidden="true" class="relative mt-0.5 h-7 w-12 shrink-0 rounded-full transition-colors duration-300" :class="enabled ? 'bg-olive' : 'bg-sand-deep'">
                    <span class="absolute top-1 start-1 size-5 rounded-full bg-white shadow transition-transform duration-300 ease-(--ease-spring)" :class="enabled && 'translate-x-5 rtl:-translate-x-5'"></span>
                </span>
            </label>

            <div x-show="enabled" x-cloak class="space-y-5">
                <label class="block space-y-1.5">
                    <span class="text-sm font-medium text-ink-soft">{{ __('quick.hours.timezone') }}</span>
                    <div class="relative max-w-sm">
                        <select name="timezone" class="field appearance-none pe-10" dir="ltr">
                            @foreach (config('sofra.timezones') as $zone)
                                <option value="{{ $zone }}" @selected(old('timezone', $shop->timezone) === $zone)>{{ str_replace('_', ' ', $zone) }}</option>
                            @endforeach
                        </select>
                        <x-icon name="chevron-down" class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-ink-mute" />
                    </div>
                </label>

                <ul class="divide-y divide-line/70">
                    @foreach ($order as $day)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 py-3">
                            <label class="flex min-h-11 w-36 cursor-pointer items-center gap-3">
                                <input type="hidden" name="hours[{{ $day }}][open]" :value="days[{{ $day }}].open ? 1 : 0">
                                <input type="checkbox" x-model="days[{{ $day }}].open" class="size-5 accent-olive">
                                <span class="font-medium" :class="!days[{{ $day }}].open && 'text-ink-mute'">{{ __("quick.days.{$day}") }}</span>
                            </label>
                            <div x-show="days[{{ $day }}].open" class="flex items-center gap-2" dir="ltr">
                                <input type="time" name="hours[{{ $day }}][from]" x-model="days[{{ $day }}].from" aria-label="{{ __('quick.hours.from') }} — {{ __("quick.days.{$day}") }}" class="{{ $timeInput }}">
                                <span class="text-ink-mute">–</span>
                                <input type="time" name="hours[{{ $day }}][to]" x-model="days[{{ $day }}].to" aria-label="{{ __('quick.hours.to') }} — {{ __("quick.days.{$day}") }}" class="{{ $timeInput }}">
                            </div>
                            <span x-show="!days[{{ $day }}].open" class="text-sm text-ink-mute">{{ __('quick.hours.closed_day') }}</span>
                        </li>
                    @endforeach
                </ul>
                <p class="text-xs text-ink-mute">{{ __('quick.hours.overnight_hint') }}</p>
            </div>
        </section>

        <p class="rounded-2xl bg-olive-soft/60 p-4 text-sm text-ink-soft ring-1 ring-olive/20">{{ __('quick.hours.display_hint') }}
            <a href="{{ route('settings.edit') }}" class="text-clay underline-offset-4 hover:underline">{{ __('app.nav.settings') }}</a>
        </p>

        <div class="sticky bottom-24 z-10 flex items-center justify-end gap-2 rounded-full bg-paper/90 p-2 shadow-lg ring-1 ring-line backdrop-blur lg:static lg:bg-transparent lg:p-0 lg:shadow-none lg:ring-0">
            <a href="{{ route('today') }}" class="btn btn-ghost">{{ __('app.actions.cancel') }}</a>
            <button type="submit" class="btn btn-primary min-w-32" :disabled="busy">
                <span x-show="!busy">{{ __('app.actions.save') }}</span>
                <span x-show="busy" x-cloak>{{ __('app.actions.saving') }}</span>
            </button>
        </div>
    </form>
</x-layouts.dashboard>
