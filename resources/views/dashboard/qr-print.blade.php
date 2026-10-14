{{-- Print sheet: one A6 table card. Use the browser's print dialog (or "Save as PDF"). --}}
<x-layouts.base :title="__('app.qr.print')" body-class="bg-sand">
    <x-slot:head>
        <style>
            @page { size: A6 portrait; margin: 0; }
            @media print {
                body { background: #fff !important; }
                .no-print { display: none !important; }
                .print-card { width: 105mm !important; box-shadow: none !important; border-radius: 0 !important; font-size: 4.6mm !important; }
                .print-sheet { padding: 0 !important; min-height: 0 !important; }
            }
        </style>
    </x-slot:head>

    <div class="no-print flex items-center justify-between gap-3 px-5 py-4">
        <a href="{{ route('qr.show') }}" class="text-sm text-ink-soft hover:text-clay">{{ __('app.qr.back') }}</a>
        <button type="button" onclick="window.print()" class="btn btn-primary px-5 py-2.5">
            <x-icon name="qr" class="size-4" /> {{ __('app.qr.print_now') }}
        </button>
    </div>

    <main class="print-sheet grid min-h-[calc(100dvh-5rem)] place-items-center p-6">
        <x-qr-card :shop="$shop" :qr="$qr" class="print-card w-[min(105mm,90vw)] rounded-3xl text-[clamp(13px,4.2vw,17px)] shadow-2xl" />
    </main>
</x-layouts.base>
