@php
    $shop = app(\App\Tenancy\CurrentShop::class)->get();
    $locales = $shop->contentLocales();
    $editing = $category->exists;
    $title = $editing ? __('app.categories.edit') : __('app.categories.new');
@endphp
<x-layouts.dashboard :title="$title">
    <x-page-header :title="$title" :back="route('categories.index')" />

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('categories.update', $category) : route('categories.store') }}"
          x-data="{ lang: @js($locales[0]), busy: false }" @submit="busy = true"
          class="reveal grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-6" style="--i: 1">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="card space-y-6 p-5 sm:p-7">
            <x-translatable-field name="name" :label="__('app.fields.name')" :locales="$locales"
                                  :values="$category->getTranslations('name')" :required="$shop->default_locale"
                                  maxlength="60" :placeholder="__('app.categories.name_placeholder')" />

            @if (count($locales) > 1)
                <p class="text-xs text-ink-mute">{{ __('app.forms.fallback_note', ['language' => \App\Support\Locales::name($shop->default_locale)]) }}</p>
            @endif

            <div class="border-t border-line pt-5">
                <x-toggle name="is_active" :label="__('app.categories.active')" :hint="__('app.categories.active_hint')" :checked="$category->is_active" />
            </div>
        </section>

        <section class="card p-5 sm:p-7">
            <x-image-input :label="__('app.fields.image')" :url="$category->imageUrl()" shape="wide" :max-side="1600"
                           :hint="__('app.categories.image_hint')" />
        </section>

        <div class="sticky bottom-24 z-10 flex items-center justify-end gap-2 rounded-full bg-paper/90 p-2 shadow-lg ring-1 ring-line backdrop-blur lg:static lg:col-span-2 lg:bg-transparent lg:p-0 lg:shadow-none lg:ring-0">
            <a href="{{ route('categories.index') }}" class="btn btn-ghost">{{ __('app.actions.cancel') }}</a>
            <button type="submit" class="btn btn-primary min-w-32" :disabled="busy">
                <span x-show="!busy">{{ __('app.actions.save') }}</span>
                <span x-show="busy" x-cloak>{{ __('app.actions.saving') }}</span>
            </button>
        </div>
    </form>

    @if ($editing)
        <div class="mt-10 flex justify-center lg:justify-start">
            <x-delete-dialog :action="route('categories.destroy', $category)"
                             :title="__('app.categories.delete_title', ['name' => $category->name])"
                             :text="$category->products_count
                                 ? __('app.categories.delete_text', ['count' => $category->products_count])
                                 : __('app.categories.delete_text_empty')">
                <x-slot:trigger><x-icon name="trash" class="size-4" /> {{ __('app.actions.delete') }}</x-slot:trigger>
            </x-delete-dialog>
        </div>
    @endif
</x-layouts.dashboard>
