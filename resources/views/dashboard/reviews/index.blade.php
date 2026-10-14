<x-layouts.dashboard :title="__('app.review.page_title')">
    <x-page-header :title="__('app.review.page_title')" :subtitle="__('app.review.page_subtitle')" />

    @if ($total === 0)
        <x-empty-state :title="__('app.review.empty_title')" :text="__('app.review.empty_text')" icon="star" />
    @else
        {{-- Summary --}}
        <section class="reveal card grid gap-6 p-6 sm:grid-cols-[auto_1fr] sm:items-center sm:gap-10 sm:p-8" style="--i: 1">
            <div class="text-center">
                <p class="font-display text-6xl font-semibold" dir="ltr">{{ number_format($average, 1) }}</p>
                <p class="mt-1 flex justify-center gap-0.5 text-saffron" dir="ltr">
                    @foreach (range(1, 5) as $n)
                        <x-star @class(['size-5', 'opacity-25' => $n > round($average)]) />
                    @endforeach
                </p>
                <p class="mt-2 text-sm text-ink-mute">{{ __('app.review.total') }}: <bdi>{{ $total }}</bdi></p>
            </div>
            <ul class="space-y-2" dir="ltr">
                @foreach (range(5, 1) as $n)
                    @php($count = (int) ($breakdown[$n] ?? 0))
                    <li class="flex items-center gap-3 text-sm">
                        <span class="w-3 text-ink-soft">{{ $n }}</span>
                        <span class="h-2.5 flex-1 overflow-hidden rounded-full bg-sand-deep">
                            <span class="block h-full rounded-full bg-saffron" style="width: {{ round($count / $total * 100) }}%"></span>
                        </span>
                        <span class="w-8 text-end text-ink-mute tabular-nums">{{ $count }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Reviews --}}
        <ul class="mt-6 space-y-3">
            @foreach ($reviews as $review)
                <li class="reveal card p-5 sm:p-6" style="--i: {{ min($loop->iteration + 1, 6) }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="flex gap-0.5 text-saffron" dir="ltr" aria-label="{{ __('app.review.stars', ['count' => $review->rating]) }}">
                                @foreach (range(1, 5) as $n)
                                    <x-star @class(['size-5', 'opacity-25' => $n > $review->rating]) />
                                @endforeach
                            </p>
                            <p class="mt-2 font-semibold">{{ $review->name ?: __('app.review.anonymous') }}
                                @if ($review->contact)
                                    <span class="ms-2 text-sm font-normal text-ink-mute" dir="ltr">{{ $review->contact }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <time class="text-sm text-ink-mute" datetime="{{ $review->created_at->toIso8601String() }}" dir="ltr">{{ $review->created_at->format('Y-m-d H:i') }}</time>
                            <form method="POST" action="{{ route('reviews.destroy', $review) }}"
                                  @submit="if (! confirm(@js(__('app.review.confirm_delete')))) $event.preventDefault()" x-data>
                                @csrf
                                @method('DELETE')
                                <button class="rounded-full px-3 py-1.5 text-sm text-ink-mute transition hover:bg-danger/10 hover:text-danger">{{ __('app.review.delete') }}</button>
                            </form>
                        </div>
                    </div>

                    @if ($review->comment)
                        <p class="mt-3 leading-relaxed whitespace-pre-line text-ink-soft">{{ $review->comment }}</p>
                    @endif

                    @if ($review->photo_path)
                        <a href="{{ $review->photoUrl() }}" target="_blank" rel="noopener" class="mt-4 inline-block">
                            <img src="{{ $review->photoUrl('sm') }}" alt="{{ __('app.review.photo') }}" loading="lazy" class="h-32 rounded-2xl object-cover ring-1 ring-line">
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $reviews->links() }}</div>
    @endif
</x-layouts.dashboard>