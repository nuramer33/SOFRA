<x-layouts.dashboard :title="__('app.categories.title')">
    <x-page-header :title="__('app.categories.title')" :subtitle="__('app.categories.subtitle')">
        @if ($categories->isNotEmpty())
            <x-slot:actions>
                <a href="{{ route('categories.create') }}" class="btn btn-primary">
                    <x-icon name="plus" class="size-4" /> {{ __('app.categories.new') }}
                </a>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if ($categories->isEmpty())
        <x-empty-state :title="__('app.categories.empty_title')" :text="__('app.categories.empty_text')" icon="grid">
            <x-slot:action>
                <a href="{{ route('categories.create') }}" class="btn btn-primary">
                    <x-icon name="plus" class="size-4" /> {{ __('app.categories.new') }}
                </a>
            </x-slot:action>
        </x-empty-state>
    @else
        <ol class="space-y-3">
            @foreach ($categories as $category)
                <li id="category-{{ $category->id }}" class="reveal card flex items-center gap-3 p-3 sm:gap-4 sm:p-4" style="--i: {{ min($loop->index, 8) }}">
                    <x-move-buttons :action="route('categories.position', $category)" :first="$loop->first" :last="$loop->last" />

                    <a href="{{ route('categories.edit', $category) }}" class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                        <x-thumb :src="$category->imageUrl('sm')" :label="$category->name" />
                        <span class="min-w-0">
                            <span class="flex items-center gap-2">
                                <span class="truncate font-display text-lg font-semibold sm:text-xl">{{ $category->name }}</span>
                                @unless ($category->is_active)
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-sand px-2 py-0.5 text-[11px] text-ink-soft">
                                        <x-icon name="eye-closed" class="size-3.5" /> {{ __('app.categories.hidden') }}
                                    </span>
                                @endunless
                            </span>
                            <span class="mt-0.5 flex items-center gap-3 text-sm text-ink-soft">
                                {{ __('app.categories.products_count', ['count' => $category->products_count]) }}
                                <x-translation-dots :model="$category" field="name" />
                            </span>
                        </span>
                    </a>

                    <a href="{{ route('categories.edit', $category) }}" class="hidden size-10 shrink-0 place-items-center rounded-full text-ink-soft transition hover:bg-sand hover:text-clay sm:grid"
                       aria-label="{{ __('app.actions.edit') }}" title="{{ __('app.actions.edit') }}">
                        <x-icon name="pencil" class="size-4.5" />
                    </a>
                </li>
            @endforeach
        </ol>
    @endif
</x-layouts.dashboard>
