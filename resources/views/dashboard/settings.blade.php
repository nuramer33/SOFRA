@php
    $locales = \App\Support\Locales::codes();
    $enabled = old('enabled_locales', $shop->contentLocales());
    $color = strtolower(old('brand_color', $shop->brand_color));
    $host = parse_url(config('app.url'), PHP_URL_HOST);
@endphp
<x-layouts.dashboard :title="__('app.settings.title')">
    <x-page-header :title="__('app.settings.title')" :subtitle="__('app.settings.subtitle')" />

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data"
          x-data="{ lang: @js($shop->default_locale), busy: false }" @submit="busy = true" class="space-y-4 lg:space-y-6">
        @csrf
        @method('PUT')

        {{-- Identity ------------------------------------------------------- --}}
        <x-settings-section :title="__('app.settings.section_identity')" :text="__('app.settings.section_identity_text')" :i="1">
            <x-locale-tabs :locales="$locales" :fields="['name', 'tagline']" />

            <x-translatable-field name="name" :label="__('app.fields.shop_name')" :locales="$locales"
                                  :values="$shop->getTranslations('name')" :required="$shop->default_locale" maxlength="80" />
            <x-translatable-field name="tagline" :label="__('app.fields.tagline')" :locales="$locales"
                                  :values="$shop->getTranslations('tagline')" maxlength="120"
                                  :placeholder="__('app.settings.tagline_placeholder')" />

            <div class="border-t border-line pt-6">
                <x-image-input name="logo" remove-name="remove_logo" :label="__('app.fields.logo')" :url="$shop->logoUrl()"
                               shape="circle" :max-side="1024" :hint="__('app.settings.logo_hint')" />
            </div>

            {{-- Brand color + live preview --}}
            <div class="border-t border-line pt-6" x-data="brandPicker(@js($color))">
                <span class="block text-sm font-medium text-ink-soft">{{ __('app.fields.brand_color') }}</span>
                <div class="mt-3 grid gap-6 sm:grid-cols-[minmax(0,1fr)_15rem]">
                    <div>
                        <div class="flex flex-wrap gap-2.5" role="radiogroup" aria-label="{{ __('app.fields.brand_color') }}">
                            @foreach (config('sofra.brand_presets') as $preset)
                                <button type="button" role="radio" @click="color = @js(strtolower($preset))"
                                        :aria-checked="(color === @js(strtolower($preset))).toString()"
                                        class="grid size-10 place-items-center rounded-full ring-offset-2 ring-offset-paper transition duration-200 hover:scale-110 active:scale-95"
                                        :class="color === @js(strtolower($preset)) && 'ring-2 ring-ink scale-110'"
                                        style="background-color: {{ $preset }}" aria-label="{{ $preset }}">
                                    {{-- Plain span: Blade doesn't compile @js inside component attributes --}}
                                    <span x-show="color === @js(strtolower($preset))" :style="{ color: foreground }">
                                        <x-icon name="check" class="size-4" />
                                    </span>
                                </button>
                            @endforeach
                            <label class="relative grid size-10 cursor-pointer place-items-center rounded-full bg-[conic-gradient(from_90deg,#a8462a,#e0a43b,#56653a,#1f6f6b,#263a5c,#6b3a5e,#a8462a)] transition hover:scale-110"
                                   title="{{ __('app.settings.brand_custom') }}">
                                <span class="sr-only">{{ __('app.settings.brand_custom') }}</span>
                                <span class="size-5 rounded-full ring-2 ring-white" :style="{ backgroundColor: color }"></span>
                                <input type="color" x-model="color" class="absolute inset-0 size-full cursor-pointer opacity-0">
                            </label>
                        </div>
                        <input type="hidden" name="brand_color" :value="color" value="{{ $color }}">
                        <p class="mt-3 font-mono text-xs text-ink-mute" dir="ltr" x-text="color.toUpperCase()"></p>
                        @error('brand_color') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    </div>

                    {{-- Mini menu card in the chosen color --}}
                    <div class="rounded-3xl bg-white/80 p-4 ring-1 ring-line" aria-hidden="true">
                        <p class="text-[11px] text-ink-mute">{{ __('app.settings.preview') }}</p>
                        <div class="mt-3 flex items-center gap-2.5">
                            <span class="grid size-9 place-items-center rounded-full font-display font-semibold transition-colors duration-300"
                                  :style="{ backgroundColor: color, color: foreground }">{{ mb_substr($shop->name, 0, 1) }}</span>
                            <span class="truncate font-display font-semibold">{{ $shop->name }}</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between rounded-2xl bg-paper p-2.5">
                            <span class="text-sm">{{ __('app.settings.preview_item') }}</span>
                            <span class="text-sm font-semibold transition-colors duration-300" :style="{ color: color }">{{ $shop->formatPrice(85) }}</span>
                        </div>
                        <div class="mt-3 rounded-full py-2 text-center text-sm font-medium transition-colors duration-300"
                             :style="{ backgroundColor: color, color: foreground }">{{ __('app.settings.preview_button') }}</div>
                    </div>
                </div>
            </div>
        </x-settings-section>

        {{-- Location & contact ---------------------------------------------- --}}
        <x-settings-section :title="__('shopinfo.settings.section_contact')" :text="__('shopinfo.settings.section_contact_text')" :i="2">
            <x-locale-tabs :locales="$locales" :fields="['address', 'hours']" />

            <x-translatable-field name="address" :label="__('shopinfo.settings.address')" :locales="$locales"
                                  :values="$shop->getTranslations('address')" maxlength="200"
                                  :placeholder="__('shopinfo.settings.address_placeholder')" />
            <x-translatable-field name="hours" :label="__('shopinfo.settings.hours')" :locales="$locales"
                                  :values="$shop->getTranslations('hours')" maxlength="150"
                                  :placeholder="__('shopinfo.settings.hours_placeholder')" />

            <div class="grid gap-5 border-t border-line pt-6 sm:grid-cols-2">
                <x-field name="maps_url" type="url" :label="__('shopinfo.settings.maps_url')" :hint="__('shopinfo.settings.maps_hint')"
                         :value="$shop->maps_url" dir="ltr" inputmode="url" maxlength="500" placeholder="https://maps.app.goo.gl/…" />
                <x-field name="instagram" :label="__('shopinfo.settings.instagram')" :hint="__('shopinfo.settings.instagram_hint')"
                         :value="$shop->instagram" dir="ltr" autocapitalize="none" autocorrect="off" maxlength="60" placeholder="kahveevi" />
            </div>
        </x-settings-section>

        {{-- Orders ---------------------------------------------------------- --}}
        <x-settings-section id="orders" :title="__('app.settings.section_orders')" :text="__('app.settings.section_orders_text')" :i="2">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="whatsapp_number" :label="__('app.fields.whatsapp')" :hint="__('app.settings.whatsapp_hint')">
                    <x-slot:input>
                        <div dir="ltr" class="relative">
                            <x-icon name="whatsapp" class="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-olive" />
                            <span class="pointer-events-none absolute start-11 top-1/2 -translate-y-1/2 text-ink-mute">+</span>
                            <input id="whatsapp_number" name="whatsapp_number" type="tel" inputmode="tel" autocomplete="tel"
                                   value="{{ old('whatsapp_number', $shop->whatsapp_number) }}" placeholder="905551234567"
                                   @if ($errors->has('whatsapp_number')) aria-invalid="true" @endif
                                   class="field ps-14 tabular-nums">
                        </div>
                    </x-slot:input>
                </x-field>

                <x-field name="currency" :label="__('app.fields.currency')">
                    <x-slot:input>
                        <div class="relative">
                            <select id="currency" name="currency" class="field appearance-none pe-10">
                                @foreach (config('sofra.currencies') as $currency)
                                    <option value="{{ $currency }}" @selected(old('currency', $shop->currency) === $currency)>
                                        {{ $currency }} · {{ \App\Support\Money::symbol($currency) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-ink-mute" />
                        </div>
                    </x-slot:input>
                </x-field>
            </div>

            @if ($shop->whatsapp_number)
                {{-- Let the owner check that orders reach the right number --}}
                <div class="flex flex-col gap-3 rounded-2xl bg-olive-soft/60 p-4 sm:flex-row sm:items-center">
                    <p class="flex-1 text-sm text-ink-soft">{{ __('app.settings.whatsapp_test_hint') }}</p>
                    <a href="https://wa.me/{{ $shop->whatsapp_number }}?text={{ rawurlencode(__('app.settings.whatsapp_test_message', ['shop' => $shop->name])) }}"
                       target="_blank" rel="noopener" class="btn shrink-0 bg-[#0f7d43] px-4 py-2.5 text-sm text-white hover:bg-[#0c6a39]">
                        <x-icon name="whatsapp" class="size-4" /> {{ __('app.settings.whatsapp_test') }}
                    </a>
                </div>
            @endif

            <fieldset class="border-t border-line pt-6">
                <legend class="text-sm font-medium text-ink-soft">{{ __('app.fields.order_types') }}</legend>
                <p class="text-xs text-ink-mute">{{ __('app.settings.order_types_hint') }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (\App\Models\Shop::ORDER_TYPES as $type)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="order_types[]" value="{{ $type }}" class="peer sr-only"
                                   @checked(in_array($type, old('order_types', $shop->orderTypes())))>
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/60 px-4 py-2.5 font-medium text-ink-soft ring-1 ring-line transition
                                         peer-checked:bg-ink peer-checked:text-paper peer-checked:ring-ink peer-focus-visible:ring-4 peer-focus-visible:ring-clay/20">
                                <x-icon name="check" class="hidden size-4 [input:checked+span_&]:block" />
                                {{ __('app.cart.types.'.$type) }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('order_types') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </fieldset>

            <div class="grid gap-5 border-t border-line pt-6 sm:grid-cols-2">
                <x-field name="delivery_fee" type="number" :label="__('shopinfo.settings.delivery_fee')" :hint="__('shopinfo.settings.delivery_fee_hint')"
                         :value="$shop->delivery_fee !== null ? rtrim(rtrim($shop->delivery_fee, '0'), '.') : null" dir="ltr" inputmode="decimal" min="0" step="any" />
                <x-field name="min_order" type="number" :label="__('shopinfo.settings.min_order')" :hint="__('shopinfo.settings.min_order_hint')"
                         :value="$shop->min_order !== null ? rtrim(rtrim($shop->min_order, '0'), '.') : null" dir="ltr" inputmode="decimal" min="0" step="any" />
            </div>
        </x-settings-section>

        {{-- Languages ------------------------------------------------------- --}}
        <x-settings-section :title="__('app.settings.section_languages')" :text="__('app.settings.section_languages_text')" :i="3">
            <fieldset>
                <legend class="text-sm font-medium text-ink-soft">{{ __('app.fields.languages') }}</legend>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($locales as $locale)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="enabled_locales[]" value="{{ $locale }}" class="peer sr-only" @checked(in_array($locale, $enabled))>
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/60 px-4 py-2.5 font-medium text-ink-soft ring-1 ring-line transition
                                         peer-checked:bg-ink peer-checked:text-paper peer-checked:ring-ink peer-focus-visible:ring-4 peer-focus-visible:ring-clay/20" lang="{{ $locale }}">
                                <x-icon name="check" class="hidden size-4 [input:checked+span_&]:block" />
                                {{ \App\Support\Locales::name($locale) }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('enabled_locales') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </fieldset>

            <fieldset class="border-t border-line pt-6">
                <legend class="text-sm font-medium text-ink-soft">{{ __('app.fields.default_language') }}</legend>
                <p class="text-xs text-ink-mute">{{ __('app.settings.default_language_hint') }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($locales as $locale)
                        <label class="cursor-pointer">
                            <input type="radio" name="default_locale" value="{{ $locale }}" class="peer sr-only"
                                   @checked(old('default_locale', $shop->default_locale) === $locale)>
                            <span class="inline-flex items-center gap-2 rounded-full bg-white/60 px-4 py-2.5 font-medium text-ink-soft ring-1 ring-line transition
                                         peer-checked:bg-clay peer-checked:text-paper peer-checked:ring-clay peer-focus-visible:ring-4 peer-focus-visible:ring-clay/20" lang="{{ $locale }}">
                                <x-icon name="check" class="hidden size-4 [input:checked+span_&]:block" />
                                {{ \App\Support\Locales::name($locale) }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('default_locale') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </fieldset>

            {{-- Language of this dashboard (the only place it can be changed) --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-6">
                <div>
                    <p class="text-sm font-medium text-ink-soft">{{ __('analytics.interface_language') }}</p>
                    <p class="text-xs text-ink-mute">{{ __('analytics.interface_language_hint') }}</p>
                </div>
                <x-locale-switcher />
            </div>
        </x-settings-section>

        {{-- Link ------------------------------------------------------------ --}}
        <x-settings-section :title="__('app.settings.section_link')" :text="__('app.settings.section_link_text')" :i="4">
            <x-field name="slug" :label="__('app.auth.slug')" :hint="__('app.auth.slug_hint')">
                <x-slot:input>
                    <div dir="ltr" @class([
                            'field flex items-center p-0 focus-within:border-clay focus-within:bg-white focus-within:ring-4 focus-within:ring-clay/15',
                            '!border-danger' => $errors->has('slug'),
                        ])>
                        <span class="ps-4 text-ink-mute select-none">{{ $host }}/</span>
                        <input id="slug" name="slug" value="{{ old('slug', $shop->slug) }}" required minlength="3" maxlength="40"
                               autocapitalize="none" autocorrect="off" spellcheck="false"
                               @if ($errors->has('slug')) aria-invalid="true" @endif
                               class="min-w-0 flex-1 bg-transparent py-3.5 pe-4 font-medium outline-none">
                    </div>
                </x-slot:input>
            </x-field>
            <p class="flex items-start gap-2 rounded-2xl bg-saffron/15 px-4 py-3 text-sm text-clay-deep">
                <x-icon name="qr" class="mt-0.5 size-4" /> {{ __('app.settings.slug_warning') }}
            </p>
        </x-settings-section>

        {{-- Menu look ------------------------------------------------------- --}}
        <x-settings-section :title="__('templates.title')" :text="__('templates.text')" :i="5">
            <fieldset>
                <legend class="sr-only">{{ __('templates.title') }}</legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (config('sofra.templates') as $template)
                        <label class="cursor-pointer">
                            <input type="radio" name="template" value="{{ $template }}" class="peer sr-only" @checked(old('template', $shop->template) === $template)>
                            <span class="block rounded-3xl bg-white/60 p-3 ring-1 ring-line transition peer-checked:ring-2 peer-checked:ring-clay peer-focus-visible:ring-4 peer-focus-visible:ring-clay/30">
                                {{-- A tiny sketch of each look --}}
                                @if ($template === 'luxe')
                                    <span class="flex h-28 items-end justify-center gap-2 overflow-hidden rounded-2xl px-4 pt-4" style="background: color-mix(in oklab, {{ $shop->brand_color }} 30%, #090807)" aria-hidden="true">
                                        <span class="h-20 w-12 rounded-t-full" style="background: color-mix(in oklab, {{ $shop->brand_color }} 36%, #f1e4c6)"></span>
                                        <span class="mb-[-0.75rem] h-20 w-12 rounded-t-full border" style="border-color: color-mix(in oklab, {{ $shop->brand_color }} 36%, #f1e4c6)"></span>
                                    </span>
                                @else
                                    <span class="flex h-28 items-center justify-center gap-3 overflow-hidden rounded-2xl bg-paper px-4" aria-hidden="true">
                                        <span class="arch h-16 w-12 bg-clay"></span>
                                        <span class="space-y-2"><span class="block h-2 w-16 rounded-full bg-sand-deep"></span><span class="block h-2 w-10 rounded-full bg-sand-deep"></span><span class="block h-2 w-14 rounded-full bg-sand-deep"></span></span>
                                    </span>
                                @endif
                                <span class="mt-3 flex items-center justify-between gap-2 px-1">
                                    <span class="font-semibold">{{ __("templates.{$template}.name") }}</span>
                                    <x-icon name="check" class="hidden size-4 text-clay [input:checked+span_&]:block" />
                                </span>
                                <span class="mt-1 block px-1 text-sm text-ink-soft">{{ __("templates.{$template}.text") }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('template') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror
            </fieldset>
        </x-settings-section>

        {{-- Analytics retention --------------------------------------------- --}}
        <x-settings-section id="analytics" :title="__('analytics.section_title')" :text="__('analytics.section_text')" :i="6">
            <x-field name="analytics_retention_days" :label="__('analytics.retention.label')" :hint="__('analytics.retention.hint')">
                <x-slot:input>
                    <div class="relative">
                        <select id="analytics_retention_days" name="analytics_retention_days" class="field appearance-none pe-10">
                            @foreach (\App\Support\Analytics::RETENTION_OPTIONS as $days)
                                <option value="{{ $days }}" @selected((int) old('analytics_retention_days', $shop->analytics_retention_days) === $days)>
                                    {{ __('analytics.retention.days', ['days' => $days]) }}
                                </option>
                            @endforeach
                        </select>
                        <x-icon name="chevron-down" class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-ink-mute" />
                    </div>
                </x-slot:input>
            </x-field>
        </x-settings-section>

        <div class="sticky bottom-24 z-10 flex justify-end rounded-full bg-paper/90 p-2 shadow-lg ring-1 ring-line backdrop-blur lg:bottom-6">
            <button type="submit" class="btn btn-primary min-w-40" :disabled="busy">
                <span x-show="!busy">{{ __('app.actions.save') }}</span>
                <span x-show="busy" x-cloak>{{ __('app.actions.saving') }}</span>
            </button>
        </div>
    </form>
</x-layouts.dashboard>
