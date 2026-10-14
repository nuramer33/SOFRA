@php
    $shop = app(\App\Tenancy\CurrentShop::class)->get();
    $locales = $shop->contentLocales();
    $symbol = \App\Support\Money::symbol($shop->currency);
    $empty = ['ar' => '', 'tr' => '', 'en' => ''];

    $initial = old('suggestions') ?? $rows->map(fn ($row) => [
        'suggested_product_id' => $row->suggested_product_id,
        'discount_type' => $row->discount_type ?? '',
        'discount_value' => $row->discount_value !== null ? rtrim(rtrim((string) $row->discount_value, '0'), '.') : '',
        'is_active' => $row->is_active,
        'title' => $row->getTranslations('title'),
        'description' => $row->getTranslations('description'),
    ])->all();

    $initial = collect($initial)->map(fn ($row) => [
        ...$row,
        'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'discount_type' => $row['discount_type'] ?? '',
        'discount_value' => $row['discount_value'] ?? '',
        'title' => [...$empty, ...($row['title'] ?? [])],
        'description' => [...$empty, ...($row['description'] ?? [])],
    ])->values()->all();

    $prices = $options->mapWithKeys(fn ($option) => [$option->id => (float) $option->price])->all();
    $title = __('suggestions.page_title', ['name' => $product->name]);
@endphp
<x-layouts.dashboard :title="$title">
    <x-page-header :title="__('suggestions.menu_link')" :subtitle="$product->name" :back="route('products.edit', $product)" />

    @if ($errors->any())
        <div role="alert" class="reveal mb-4 rounded-2xl bg-danger/10 p-4 text-sm text-danger ring-1 ring-danger/20">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($options->isEmpty())
        <x-empty-state :title="__('suggestions.no_products_title')" :text="__('suggestions.no_products_text')" icon="cup" />
    @else
        <form method="POST" action="{{ route('products.suggestions.update', $product) }}"
              x-data="{
                  lang: @js($locales[0]),
                  busy: false,
                  prices: @js($prices),
                  rows: @js($initial),
                  blank() { return { suggested_product_id: '', discount_type: '', discount_value: '', is_active: true, title: @js($empty), description: @js($empty) }; },
                  add() { if (this.rows.length < 8) this.rows.push(this.blank()); },
                  final(row) {
                      const price = this.prices[row.suggested_product_id];
                      const value = parseFloat(String(row.discount_value).replace(',', '.'));
                      if (!price || !value || value <= 0) return null;
                      const off = row.discount_type === 'percent' ? price * value / 100 : row.discount_type === 'fixed' ? value : 0;
                      return off > 0 && off < price ? Math.round((price - off) * 100) / 100 : null;
                  },
              }"
              @submit="busy = true" class="reveal space-y-4" style="--i: 1">
            @csrf
            @method('PUT')

            <p class="rounded-2xl bg-olive-soft/60 p-4 text-sm text-ink-soft ring-1 ring-olive/20">{{ __('suggestions.intro') }}</p>

            @if (count($locales) > 1)
                <x-locale-tabs :locales="$locales" :fields="[]" class="card p-4" />
            @endif

            <template x-for="(row, index) in rows" :key="index">
                <section class="card space-y-5 p-5 sm:p-7">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-display text-xl font-semibold" x-text="@js(__('suggestions.suggestion_n')).replace(':n', index + 1)"></h2>
                        <button type="button" @click="rows.splice(index, 1)" class="inline-flex min-h-11 items-center gap-1.5 rounded-full px-3 text-sm text-danger transition hover:bg-danger/10">
                            <x-icon name="trash" class="size-4" /> {{ __('app.actions.delete') }}
                        </button>
                    </div>

                    <label class="block space-y-1.5">
                        <span class="text-sm font-medium text-ink-soft">{{ __('suggestions.fields.product') }}</span>
                        <div class="relative">
                            <select :name="`suggestions[${index}][suggested_product_id]`" x-model="row.suggested_product_id" required class="field appearance-none pe-10">
                                <option value="" disabled>{{ __('suggestions.choose_product') }}</option>
                                @foreach ($options as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }} · {{ $shop->formatPrice($option->price) }}</option>
                                @endforeach
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-ink-mute" />
                        </div>
                    </label>

                    @foreach ($locales as $locale)
                        <div x-show="lang === @js($locale)" @if (! $loop->first) x-cloak @endif class="space-y-4">
                            <label class="block space-y-1.5">
                                <span class="flex items-center gap-2 text-sm font-medium text-ink-soft">
                                    {{ __('suggestions.fields.title') }}
                                    <span class="rounded-full bg-sand px-2 py-0.5 text-[11px] font-normal" lang="{{ $locale }}">{{ \App\Support\Locales::name($locale) }}</span>
                                    @if ($locale === $shop->default_locale)
                                        <span class="text-[11px] font-normal text-ink-mute">· {{ __('suggestions.optional_but_default') }}</span>
                                    @endif
                                </span>
                                <input :name="`suggestions[${index}][title][{{ $locale }}]`" x-model="row.title.{{ $locale }}" maxlength="80"
                                       lang="{{ $locale }}" dir="{{ \App\Support\Locales::dir($locale) }}"
                                       placeholder="{{ $locale === $shop->default_locale ? __('suggestions.title_placeholder') : '' }}" class="field">
                            </label>
                            <label class="block space-y-1.5">
                                <span class="flex items-center gap-2 text-sm font-medium text-ink-soft">
                                    {{ __('suggestions.fields.description') }}
                                    <span class="rounded-full bg-sand px-2 py-0.5 text-[11px] font-normal" lang="{{ $locale }}">{{ \App\Support\Locales::name($locale) }}</span>
                                </span>
                                <textarea :name="`suggestions[${index}][description][{{ $locale }}]`" x-model="row.description.{{ $locale }}" rows="2" maxlength="200"
                                          lang="{{ $locale }}" dir="{{ \App\Support\Locales::dir($locale) }}" class="field resize-y"></textarea>
                            </label>
                        </div>
                    @endforeach
                    @if (count($locales) > 1)
                        <p class="text-xs text-ink-mute">{{ __('app.forms.fallback_note', ['language' => \App\Support\Locales::name($shop->default_locale)]) }}</p>
                    @endif

                    {{-- Optional discount --}}
                    <fieldset class="space-y-3 border-t border-line pt-5">
                        <legend class="text-sm font-medium text-ink-soft">{{ __('suggestions.fields.discount') }}</legend>
                        <div class="flex flex-wrap gap-2" role="radiogroup">
                            @foreach (['' => __('suggestions.discount.none'), 'percent' => __('suggestions.discount.percent'), 'fixed' => __('suggestions.discount.fixed', ['symbol' => $symbol])] as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" :name="`suggestions[${index}][discount_type]`" value="{{ $value }}" x-model="row.discount_type" class="peer sr-only">
                                    <span class="inline-flex min-h-11 items-center gap-2 rounded-full bg-white/60 px-4 font-medium text-ink-soft ring-1 ring-line transition
                                                 peer-checked:bg-ink peer-checked:text-paper peer-checked:ring-ink peer-focus-visible:ring-4 peer-focus-visible:ring-clay/20">
                                        <x-icon name="check" class="hidden size-4 [input:checked+span_&]:block" /> {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div x-show="row.discount_type" x-cloak class="space-y-2">
                            <div dir="ltr" class="relative max-w-48">
                                <input :name="`suggestions[${index}][discount_value]`" x-model="row.discount_value" inputmode="decimal" autocomplete="off"
                                       :required="!!row.discount_type" aria-label="{{ __('suggestions.fields.discount_value') }}"
                                       class="field pe-12 font-medium tabular-nums rtl:text-right">
                                <span class="pointer-events-none absolute inset-y-0 end-4 grid place-items-center text-ink-mute" x-text="row.discount_type === 'percent' ? '%' : @js($symbol)"></span>
                            </div>
                            <p class="text-sm text-ink-soft">{{ __('suggestions.discount.how') }}</p>
                            <p x-show="final(row) !== null" class="text-sm font-medium text-olive" x-text="@js(__('suggestions.discount.preview')).replace(':price', final(row) + ' ' + @js($symbol))"></p>
                        </div>
                    </fieldset>

                    <div class="border-t border-line pt-4">
                        <label class="flex min-h-11 cursor-pointer items-center gap-3">
                            <input type="hidden" :name="`suggestions[${index}][is_active]`" value="0">
                            <input type="checkbox" :name="`suggestions[${index}][is_active]`" value="1" x-model="row.is_active" class="size-5 accent-olive">
                            <span class="text-sm font-medium">{{ __('suggestions.fields.active') }}</span>
                        </label>
                    </div>
                </section>
            </template>

            <button type="button" @click="add()" x-show="rows.length < 8"
                    class="btn btn-ghost w-full justify-center border border-dashed border-line">
                <x-icon name="plus" class="size-4" /> {{ __('suggestions.add') }}
            </button>

            <div class="sticky bottom-24 z-10 flex items-center justify-end gap-2 rounded-full bg-paper/90 p-2 shadow-lg ring-1 ring-line backdrop-blur lg:static lg:bg-transparent lg:p-0 lg:shadow-none lg:ring-0">
                <a href="{{ route('products.edit', $product) }}" class="btn btn-ghost">{{ __('app.actions.cancel') }}</a>
                <button type="submit" class="btn btn-primary min-w-32" :disabled="busy">
                    <span x-show="!busy">{{ __('app.actions.save') }}</span>
                    <span x-show="busy" x-cloak>{{ __('app.actions.saving') }}</span>
                </button>
            </div>
        </form>
    @endif
</x-layouts.dashboard>
