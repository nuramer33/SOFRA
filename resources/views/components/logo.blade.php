{{-- Brand mark (a table setting drawn in one line) + wordmark. The mark is a CSS mask, so it takes the page colour. --}}
@props(['tone' => 'ink', 'mark' => false])
<span {{ $attributes->class(['logo group inline-flex items-center gap-2.5', 'text-ink' => $tone === 'ink', 'text-paper' => $tone === 'paper']) }}>
    <span class="logo-mark size-9 shrink-0 {{ $tone === 'paper' ? 'bg-paper' : 'bg-clay' }}"
          style="--mark: url('{{ asset('icons/mark.png') }}')" role="img" aria-label="{{ __('app.brand') }}"></span>
    @unless ($mark)
        <span class="font-display text-xl font-semibold leading-none">{{ __('app.brand') }}</span>
    @endunless
</span>