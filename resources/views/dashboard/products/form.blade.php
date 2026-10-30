@php
    $shop = app(\App\Tenancy\CurrentShop::class)->get();
    $locales = $shop->contentLocales();
    $editing = $product->exists;
    $title = $editing ? __('app.products.edit') : __('app.products.new');
    $symbol = \App\Support\Money::symbol($shop->currency);
    $sectionTitle = 'mb-5 font-display text-xl font-semibold';
@endphp
<x-layouts.dashboard :title="$title">
    <x-page-header :title="$title" :back="route('products.index')" />

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('products.update', $product) : route('products.store') }}"
          x-data="{ lang: @js($locales[0]), busy: false }" @submit="busy = true"
          class="reveal grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-6" style="--i: 1">
        @csrf
        @if ($editing) @method('PUT') @endif

        {{-- Photo: first on phones (it's the fun part), sidebar on desktop --}}
        <section class="card p-5 sm:p-7 lg:col-start-2 lg:row-start-1">
            <x-image-input :label="__('app.fields.image')" :url="$product->imageUrl()" shape="arch" :max-side="2000" />
        </section>

        <div class="space-y-4 lg:col-start-1 lg:row-span-2 lg:row-start-1 lg:space-y-6">
            <section class="card space-y-6 p-5 sm:p-7">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="{{ $sectionTitle }} !mb-0">{{ __('app.products.section_details') }}</h2>
                </div>

                <x-translatable-field name="name" :label="__('app.fields.name')" :locales="$locales"
                                      :values="$product->getTranslations('name')" :required="$shop->default_locale"
                                      maxlength="80" :placeholder="__('app.products.name_placeholder')" />

                <x-translatable-field name="description" :label="__('app.fields.description')" :locales="$locales"
                                      :values="$product->getTranslations('description')" multiline
                                      maxlength="400" :placeholder="__('app.products.description_placeholder')" />

                @if (count($locales) > 1)
                    <p class="text-xs text-ink-mute">{{ __('app.forms.fallback_note', ['language' => \App\Support\Locales::name($shop->default_locale)]) }}</p>
                @endif

                <x-field name="category_id" :label="__('app.fields.category')">
                    <x-slot:input>
                        <div class="relative">
                            <select id="category_id" name="category_id" class="field appearance-none pe-10" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute end-4 top-1/2 size-4 -translate-y-1/2 text-ink-mute" />
                        </div>
                    </x-slot:input>
                </x-field>
            </section>

            <section class="card p-5 sm:p-7">
                <h2 class="{{ $sectionTitle }}">{{ __('app.products.section_price') }}</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach (['price' => __('app.products.price_hint', ['currency' => $shop->currency]), 'compare_at_price' => __('app.products.compare_hint')] as $field => $hint)
                        <x-field :name="$field" :label="__('app.fields.'.$field)" :hint="$hint">
                            <x-slot:input>
                                <div dir="ltr" class="relative">
                                    <input id="{{ $field }}" name="{{ $field }}" inputmode="decimal" autocomplete="off"
                                           value="{{ old($field, $product->{$field} !== null ? rtrim(rtrim((string) $product->{$field}, '0'), '.') : '') }}"
                                           @if ($field === 'price') required @endif
                                           @if ($errors->has($field)) aria-invalid="true" @endif
                                           class="field pe-16 font-medium tabular-nums rtl:text-right">
                                    <span class="pointer-events-none absolute inset-y-0 end-4 grid place-items-center text-ink-mute">{{ $symbol }}</span>
                                </div>
                            </x-slot:input>
                        </x-field>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="card space-y-4 p-5 sm:p-7 lg:col-start-2 lg:row-start-2">
            <h2 class="{{ $sectionTitle }} !mb-1">{{ __('app.products.section_visibility') }}</h2>
            <x-toggle name="is_available" :label="__('app.products.available')" :hint="__('app.products.available_hint')" :checked="$product->is_available" />
            <div class="border-t border-line"></div>
            <x-toggle name="is_featured" :label="__('app.products.featured')" :hint="__('app.products.featured_hint')" :checked="(bool) $product->is_featured" />
        </section>

        <div class="sticky bottom-24 z-10 flex items-center justify-end gap-2 rounded-full bg-paper/90 p-2 shadow-lg ring-1 ring-line backdrop-blur lg:static lg:col-span-2 lg:bg-transparent lg:p-0 lg:shadow-none lg:ring-0">
            <a href="{{ route('products.index') }}" class="btn btn-ghost">{{ __('app.actions.cancel') }}</a>
            <button type="submit" class="btn btn-primary min-w-32" :disabled="busy">
                <span x-show="!busy">{{ __('app.actions.save') }}</span>
                <span x-show="busy" x-cloak>{{ __('app.actions.saving') }}</span>
            </button>
        </div>
    </form>

    @if ($editing)
        <a href="{{ route('products.suggestions.edit', $product) }}"
           class="reveal card mt-4 flex items-center justify-between gap-4 p-5 transition hover:ring-clay/40 sm:p-6" style="--i: 2">
            <span>
                <span class="block font-display text-lg font-semibold">{{ __('suggestions.menu_link') }}</span>
                <span class="mt-0.5 block text-sm text-ink-soft">{{ __('suggestions.form_card_text') }}</span>
            </span>
            <x-icon name="arrow" class="flip-rtl size-5 shrink-0 text-clay" />
        </a>

        <div class="mt-10 flex justify-center lg:justify-start">
            <x-delete-dialog :action="route('products.destroy', $product)"
                             :title="__('app.products.delete_title', ['name' => $product->name])"
                             :text="__('app.products.delete_text')">
                <x-slot:trigger><x-icon name="trash" class="size-4" /> {{ __('app.actions.delete') }}</x-slot:trigger>
            </x-delete-dialog>
        </div>
    @endif
</x-layouts.dashboard>
